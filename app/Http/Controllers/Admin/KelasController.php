<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttachDosenToKelasRequest;
use App\Http\Requests\Admin\AttachMahasiswaToKelasRequest;
use App\Http\Requests\Admin\StoreKelasRequest;
use App\Http\Requests\Admin\UpdateKelasRequest;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KelasController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'prodi' => ['nullable', 'string', 'max:120'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:14'],
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
        ]);

        $kelas = Kelas::query()
            ->withCount(['mahasiswa', 'dosen', 'jadwal'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nama_kelas', 'like', "%{$search}%")
                        ->orWhere('prodi', 'like', "%{$search}%")
                        ->orWhere('tahun_akademik', 'like', "%{$search}%");
                });
            })
            ->when($filters['prodi'] ?? null, fn ($query, string $prodi) => $query->where('prodi', $prodi))
            ->when($filters['semester'] ?? null, fn ($query, int $semester) => $query->where('semester', $semester))
            ->when($filters['tahun_akademik'] ?? null, fn ($query, string $tahun) => $query->where('tahun_akademik', $tahun))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Kelas $kelas) => $this->serializeKelas($kelas));

        return Inertia::render('Admin/Kelas/Index', [
            'kelas' => $kelas,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'prodi' => $filters['prodi'] ?? '',
                'semester' => $filters['semester'] ?? '',
                'tahun_akademik' => $filters['tahun_akademik'] ?? '',
            ],
            'filterOptions' => [
                'prodi' => Kelas::query()->select('prodi')->distinct()->orderBy('prodi')->pluck('prodi'),
                'semester' => Kelas::query()->select('semester')->distinct()->orderBy('semester')->pluck('semester'),
                'tahun_akademik' => Kelas::query()->select('tahun_akademik')->distinct()->orderByDesc('tahun_akademik')->pluck('tahun_akademik'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Kelas/Create');
    }

    public function store(StoreKelasRequest $request): RedirectResponse
    {
        Kelas::create($request->validated());

        return redirect()
            ->route('admin.kelas.index')
            ->with('success', 'Data kelas berhasil ditambahkan.');
    }

    public function show(Kelas $kelas): Response
    {
        $kelas->load([
            'mahasiswa.user:id,name,email,is_active',
            'dosen.user:id,name,email,is_active',
        ])->loadCount(['mahasiswa', 'dosen', 'jadwal']);

        return Inertia::render('Admin/Kelas/Show', [
            'kelas' => [
                ...$this->serializeKelas($kelas),
                'mahasiswa' => $kelas->mahasiswa
                    ->map(fn (Mahasiswa $mahasiswa) => $this->serializeMahasiswaOption($mahasiswa))
                    ->values(),
                'dosen' => $kelas->dosen
                    ->map(fn (Dosen $dosen) => [
                        'id' => $dosen->id,
                        'pivot_id' => $dosen->pivot->id,
                        'name' => $dosen->user?->name,
                        'email' => $dosen->user?->email,
                        'nip' => $dosen->nip,
                        'mata_kuliah' => $dosen->pivot->mata_kuliah,
                    ])
                    ->values(),
            ],
            'mahasiswaOptions' => Mahasiswa::query()
                ->with('user:id,name,email')
                ->whereDoesntHave('kelas', fn ($query) => $query->where('kelas.id', $kelas->id))
                ->orderBy('nim')
                ->get()
                ->map(fn (Mahasiswa $mahasiswa) => $this->serializeMahasiswaOption($mahasiswa)),
            'dosenOptions' => Dosen::query()
                ->with('user:id,name,email')
                ->orderBy('nip')
                ->get()
                ->map(fn (Dosen $dosen) => [
                    'id' => $dosen->id,
                    'label' => "{$dosen->user?->name} - {$dosen->nip}",
                ]),
        ]);
    }

    public function edit(Kelas $kelas): Response
    {
        return Inertia::render('Admin/Kelas/Edit', [
            'kelas' => $this->serializeKelas($kelas),
        ]);
    }

    public function update(UpdateKelasRequest $request, Kelas $kelas): RedirectResponse
    {
        $kelas->update($request->validated());

        return redirect()
            ->route('admin.kelas.index')
            ->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        if ($kelas->jadwal()->exists()) {
            return back()->with('error', 'Kelas tidak dapat dihapus karena sudah memiliki jadwal.');
        }

        $kelas->delete();

        return redirect()
            ->route('admin.kelas.index')
            ->with('success', 'Data kelas berhasil dihapus.');
    }

    public function attachMahasiswa(AttachMahasiswaToKelasRequest $request, Kelas $kelas): RedirectResponse
    {
        $kelas->mahasiswa()->attach($request->validated('mahasiswa_id'));

        return back()->with('success', 'Mahasiswa berhasil ditambahkan ke kelas.');
    }

    public function detachMahasiswa(Kelas $kelas, Mahasiswa $mahasiswa): RedirectResponse
    {
        $kelas->mahasiswa()->detach($mahasiswa->id);

        return back()->with('success', 'Mahasiswa berhasil dihapus dari kelas.');
    }

    public function attachDosen(AttachDosenToKelasRequest $request, Kelas $kelas): RedirectResponse
    {
        $data = $request->validated();

        $kelas->dosen()->attach($data['dosen_id'], [
            'mata_kuliah' => $data['mata_kuliah'],
        ]);

        return back()->with('success', 'Dosen pengampu berhasil ditambahkan.');
    }

    public function detachDosen(Kelas $kelas, int $kelasDosen): RedirectResponse
    {
        DB::table('kelas_dosen')
            ->where('kelas_id', $kelas->id)
            ->where('id', $kelasDosen)
            ->delete();

        return back()->with('success', 'Dosen pengampu berhasil dihapus.');
    }

    private function serializeKelas(Kelas $kelas): array
    {
        return [
            'id' => $kelas->id,
            'nama_kelas' => $kelas->nama_kelas,
            'prodi' => $kelas->prodi,
            'semester' => $kelas->semester,
            'tahun_akademik' => $kelas->tahun_akademik,
            'mahasiswa_count' => $kelas->mahasiswa_count ?? null,
            'dosen_count' => $kelas->dosen_count ?? null,
            'jadwal_count' => $kelas->jadwal_count ?? null,
        ];
    }

    private function serializeMahasiswaOption(Mahasiswa $mahasiswa): array
    {
        return [
            'id' => $mahasiswa->id,
            'label' => "{$mahasiswa->nim} - {$mahasiswa->user?->name}",
            'nim' => $mahasiswa->nim,
            'name' => $mahasiswa->user?->name,
            'email' => $mahasiswa->user?->email,
            'prodi' => $mahasiswa->prodi,
            'angkatan' => $mahasiswa->angkatan,
        ];
    }
}
