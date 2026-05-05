<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMahasiswaRequest;
use App\Http\Requests\Admin\UpdateMahasiswaRequest;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class MahasiswaController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'angkatan' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'wajah' => ['nullable', 'in:terdaftar,belum'],
        ]);

        $mahasiswa = Mahasiswa::query()
            ->with(['user:id,name,email,is_active', 'kelas:id,nama_kelas', 'faceData:id,mahasiswa_id'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nim', 'like', "%{$search}%")
                        ->orWhere('prodi', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['kelas_id'] ?? null, function ($query, int $kelasId) {
                $query->whereHas('kelas', fn ($query) => $query->where('kelas.id', $kelasId));
            })
            ->when($filters['angkatan'] ?? null, fn ($query, int $angkatan) => $query->where('angkatan', $angkatan))
            ->when($filters['wajah'] ?? null, function ($query, string $wajah) {
                $query->where('wajah_terdaftar', $wajah === 'terdaftar');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Mahasiswa $mahasiswa) => $this->serializeMahasiswa($mahasiswa));

        return Inertia::render('Admin/Mahasiswa/Index', [
            'mahasiswa' => $mahasiswa,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'kelas_id' => $filters['kelas_id'] ?? '',
                'angkatan' => $filters['angkatan'] ?? '',
                'wajah' => $filters['wajah'] ?? '',
            ],
            'kelasOptions' => $this->kelasOptions(),
            'angkatanOptions' => Mahasiswa::query()
                ->select('angkatan')
                ->distinct()
                ->orderByDesc('angkatan')
                ->pluck('angkatan'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Mahasiswa/Create', [
            'kelasOptions' => $this->kelasOptions(),
        ]);
    }

    public function store(StoreMahasiswaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(($data['password'] ?? null) ?: 'password'),
                'role' => User::ROLE_MAHASISWA,
                'is_active' => true,
            ]);

            $mahasiswa = Mahasiswa::create([
                'user_id' => $user->id,
                'nim' => $data['nim'],
                'prodi' => $data['prodi'],
                'angkatan' => $data['angkatan'],
                'wajah_terdaftar' => false,
            ]);

            $mahasiswa->kelas()->sync($data['kelas_ids'] ?? []);
        });

        return redirect()
            ->route('admin.mahasiswa.index')
            ->with('success', 'Data mahasiswa berhasil ditambahkan.');
    }

    public function edit(Mahasiswa $mahasiswa): Response
    {
        $mahasiswa->load(['user:id,name,email,is_active', 'kelas:id']);

        return Inertia::render('Admin/Mahasiswa/Edit', [
            'mahasiswa' => [
                ...$this->serializeMahasiswa($mahasiswa),
                'kelas_ids' => $mahasiswa->kelas->pluck('id')->values(),
            ],
            'kelasOptions' => $this->kelasOptions(),
        ]);
    }

    public function update(UpdateMahasiswaRequest $request, Mahasiswa $mahasiswa): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $mahasiswa) {
            $mahasiswa->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            $mahasiswa->update([
                'nim' => $data['nim'],
                'prodi' => $data['prodi'],
                'angkatan' => $data['angkatan'],
            ]);

            $mahasiswa->kelas()->sync($data['kelas_ids'] ?? []);
        });

        return redirect()
            ->route('admin.mahasiswa.index')
            ->with('success', 'Data mahasiswa berhasil diperbarui.');
    }

    public function destroy(Mahasiswa $mahasiswa): RedirectResponse
    {
        $mahasiswa->user->delete();

        return redirect()
            ->route('admin.mahasiswa.index')
            ->with('success', 'Data mahasiswa berhasil dihapus.');
    }

    public function toggleActive(Mahasiswa $mahasiswa): RedirectResponse
    {
        $mahasiswa->user->update([
            'is_active' => ! $mahasiswa->user->is_active,
        ]);

        return back()->with('success', 'Status akun mahasiswa berhasil diperbarui.');
    }

    public function resetPassword(Mahasiswa $mahasiswa): RedirectResponse
    {
        $password = request()->string('password')->toString() ?: 'password';

        if (strlen($password) < 6) {
            return back()->with('error', 'Password minimal 6 karakter.');
        }

        $mahasiswa->user->update([
            'password' => Hash::make($password),
        ]);

        return back()->with('success', 'Password mahasiswa berhasil direset.');
    }

    private function serializeMahasiswa(Mahasiswa $mahasiswa): array
    {
        return [
            'id' => $mahasiswa->id,
            'nim' => $mahasiswa->nim,
            'name' => $mahasiswa->user?->name,
            'email' => $mahasiswa->user?->email,
            'prodi' => $mahasiswa->prodi,
            'angkatan' => $mahasiswa->angkatan,
            'wajah_terdaftar' => $mahasiswa->wajah_terdaftar,
            'is_active' => (bool) $mahasiswa->user?->is_active,
            'kelas' => $mahasiswa->kelas->map(fn (Kelas $kelas) => [
                'id' => $kelas->id,
                'nama_kelas' => $kelas->nama_kelas,
            ])->values(),
        ];
    }

    private function kelasOptions()
    {
        return Kelas::query()
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas', 'prodi', 'semester', 'tahun_akademik'])
            ->map(fn (Kelas $kelas) => [
                'id' => $kelas->id,
                'label' => "{$kelas->nama_kelas} - {$kelas->prodi} S{$kelas->semester} ({$kelas->tahun_akademik})",
            ]);
    }
}
