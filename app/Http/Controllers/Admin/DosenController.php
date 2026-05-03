<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDosenRequest;
use App\Http\Requests\Admin\UpdateDosenRequest;
use App\Models\Dosen;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class DosenController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $dosen = Dosen::query()
            ->with(['user:id,name,email,is_active'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nip', 'like', "%{$search}%")
                        ->orWhere('bidang_studi', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Dosen $dosen) => $this->serializeDosen($dosen));

        return Inertia::render('Admin/Dosen/Index', [
            'dosen' => $dosen,
            'filters' => [
                'search' => $filters['search'] ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Dosen/Create');
    }

    public function store(StoreDosenRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(($data['password'] ?? null) ?: 'password'),
                'role' => User::ROLE_DOSEN,
                'is_active' => true,
            ]);

            Dosen::create([
                'user_id' => $user->id,
                'nip' => $data['nip'],
                'bidang_studi' => $data['bidang_studi'] ?? null,
            ]);
        });

        return redirect()
            ->route('admin.dosen.index')
            ->with('success', 'Data dosen berhasil ditambahkan.');
    }

    public function edit(Dosen $dosen): Response
    {
        $dosen->load('user:id,name,email,is_active');

        return Inertia::render('Admin/Dosen/Edit', [
            'dosen' => $this->serializeDosen($dosen),
        ]);
    }

    public function update(UpdateDosenRequest $request, Dosen $dosen): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $dosen) {
            $dosen->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            $dosen->update([
                'nip' => $data['nip'],
                'bidang_studi' => $data['bidang_studi'] ?? null,
            ]);
        });

        return redirect()
            ->route('admin.dosen.index')
            ->with('success', 'Data dosen berhasil diperbarui.');
    }

    public function destroy(Dosen $dosen): RedirectResponse
    {
        $dosen->user->delete();

        return redirect()
            ->route('admin.dosen.index')
            ->with('success', 'Data dosen berhasil dihapus.');
    }

    public function toggleActive(Dosen $dosen): RedirectResponse
    {
        $dosen->user->update([
            'is_active' => ! $dosen->user->is_active,
        ]);

        return back()->with('success', 'Status akun dosen berhasil diperbarui.');
    }

    public function resetPassword(Dosen $dosen): RedirectResponse
    {
        $dosen->user->update([
            'password' => Hash::make('password'),
        ]);

        return back()->with('success', 'Password dosen berhasil direset ke password default.');
    }

    private function serializeDosen(Dosen $dosen): array
    {
        return [
            'id' => $dosen->id,
            'nip' => $dosen->nip,
            'name' => $dosen->user?->name,
            'email' => $dosen->user?->email,
            'bidang_studi' => $dosen->bidang_studi,
            'is_active' => (bool) $dosen->user?->is_active,
        ];
    }
}
