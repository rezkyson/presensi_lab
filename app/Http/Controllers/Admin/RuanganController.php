<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRuanganRequest;
use App\Http\Requests\Admin\UpdateRuanganRequest;
use App\Models\Jadwal;
use App\Models\Ruangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RuanganController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:aktif,nonaktif'],
        ]);

        $ruangan = Ruangan::query()
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nama', 'like', "%{$search}%")
                        ->orWhere('keterangan', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($query, string $status) {
                $query->where('is_active', $status === 'aktif');
            })
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Ruangan $ruangan) => $this->serializeRuangan($ruangan));

        return Inertia::render('Admin/Ruangan/Index', [
            'ruangan' => $ruangan,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Ruangan/Create');
    }

    public function store(StoreRuanganRequest $request): RedirectResponse
    {
        Ruangan::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.ruangan.index')
            ->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function edit(Ruangan $ruangan): Response
    {
        return Inertia::render('Admin/Ruangan/Edit', [
            'ruangan' => $this->serializeRuangan($ruangan),
        ]);
    }

    public function update(UpdateRuanganRequest $request, Ruangan $ruangan): RedirectResponse
    {
        $oldName = $ruangan->nama;
        $data = $request->validated();

        DB::transaction(function () use ($ruangan, $oldName, $data): void {
            $ruangan->update($data);

            if ($oldName !== $ruangan->nama) {
                Jadwal::query()
                    ->where('ruangan', $oldName)
                    ->update(['ruangan' => $ruangan->nama]);
            }
        });

        return redirect()
            ->route('admin.ruangan.index')
            ->with('success', 'Ruangan berhasil diperbarui.');
    }

    public function destroy(Ruangan $ruangan): RedirectResponse
    {
        if (Jadwal::query()->where('ruangan', $ruangan->nama)->exists()) {
            return back()->with('error', 'Ruangan tidak dapat dihapus karena masih digunakan jadwal.');
        }

        $ruangan->delete();

        return redirect()
            ->route('admin.ruangan.index')
            ->with('success', 'Ruangan berhasil dihapus.');
    }

    public function toggleActive(Ruangan $ruangan): RedirectResponse
    {
        $ruangan->update([
            'is_active' => ! $ruangan->is_active,
        ]);

        return back()->with('success', 'Status ruangan berhasil diperbarui.');
    }

    private function serializeRuangan(Ruangan $ruangan): array
    {
        return [
            'id' => $ruangan->id,
            'nama' => $ruangan->nama,
            'keterangan' => $ruangan->keterangan,
            'is_active' => $ruangan->is_active,
            'jadwal_count' => Jadwal::query()->where('ruangan', $ruangan->nama)->count(),
        ];
    }
}
