<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJadwalRequest;
use App\Http\Requests\Admin\UpdateJadwalRequest;
use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Ruangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JadwalController extends Controller
{
    private const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'dosen_id' => ['nullable', 'integer', 'exists:dosen,id'],
            'hari' => ['nullable', 'string'],
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
        ]);

        $query = Jadwal::query()
            ->with(['kelas:id,nama_kelas,prodi,tahun_akademik', 'dosen.user:id,name'])
            ->when($filters['kelas_id'] ?? null, fn ($query, int $kelasId) => $query->where('kelas_id', $kelasId))
            ->when($filters['dosen_id'] ?? null, fn ($query, int $dosenId) => $query->where('dosen_id', $dosenId))
            ->when($filters['hari'] ?? null, fn ($query, string $hari) => $query->where('hari', $hari))
            ->when($filters['tahun_akademik'] ?? null, function ($query, string $tahun) {
                $query->whereHas('kelas', fn ($query) => $query->where('tahun_akademik', $tahun));
            })
            ->orderByRaw("FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')")
            ->orderBy('jam_mulai');

        $weeklySchedules = $query->clone()
            ->get()
            ->groupBy('hari')
            ->map(fn ($items) => $items->map(fn (Jadwal $jadwal) => $this->serializeJadwal($jadwal))->values());

        $jadwal = $query
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Jadwal $jadwal) => $this->serializeJadwal($jadwal));

        return Inertia::render('Admin/Jadwal/Index', [
            'jadwal' => $jadwal,
            'weeklySchedules' => collect(self::DAYS)->mapWithKeys(fn (string $day) => [
                $day => $weeklySchedules->get($day, collect())->values(),
            ]),
            'filters' => [
                'kelas_id' => $filters['kelas_id'] ?? '',
                'dosen_id' => $filters['dosen_id'] ?? '',
                'hari' => $filters['hari'] ?? '',
                'tahun_akademik' => $filters['tahun_akademik'] ?? '',
            ],
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Jadwal/Create', [
            'options' => $this->options(),
        ]);
    }

    public function store(StoreJadwalRequest $request): RedirectResponse
    {
        Jadwal::create($request->validated());

        return redirect()
            ->route('admin.jadwal.index')
            ->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(Jadwal $jadwal): Response
    {
        return Inertia::render('Admin/Jadwal/Edit', [
            'jadwal' => $this->serializeJadwal($jadwal->load(['kelas:id,nama_kelas,prodi,tahun_akademik', 'dosen.user:id,name'])),
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateJadwalRequest $request, Jadwal $jadwal): RedirectResponse
    {
        $jadwal->update($request->validated());

        return redirect()
            ->route('admin.jadwal.index')
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Jadwal $jadwal): RedirectResponse
    {
        if ($jadwal->sesiAbsensi()->exists()) {
            return back()->with('error', 'Jadwal tidak dapat dihapus karena sudah memiliki sesi absensi.');
        }

        $jadwal->delete();

        return redirect()
            ->route('admin.jadwal.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }

    private function serializeJadwal(Jadwal $jadwal): array
    {
        return [
            'id' => $jadwal->id,
            'kelas_id' => $jadwal->kelas_id,
            'dosen_id' => $jadwal->dosen_id,
            'mata_kuliah' => $jadwal->mata_kuliah,
            'hari' => $jadwal->hari,
            'jam_mulai' => $this->formatTime($jadwal->jam_mulai),
            'jam_selesai' => $this->formatTime($jadwal->jam_selesai),
            'ruangan' => $jadwal->ruangan,
            'kelas' => $jadwal->kelas ? [
                'id' => $jadwal->kelas->id,
                'nama_kelas' => $jadwal->kelas->nama_kelas,
                'prodi' => $jadwal->kelas->prodi,
                'tahun_akademik' => $jadwal->kelas->tahun_akademik,
            ] : null,
            'dosen' => $jadwal->dosen ? [
                'id' => $jadwal->dosen->id,
                'name' => $jadwal->dosen->user?->name,
            ] : null,
        ];
    }

    private function options(): array
    {
        return [
            'days' => self::DAYS,
            'kelas' => Kelas::query()
                ->orderBy('nama_kelas')
                ->get(['id', 'nama_kelas', 'prodi', 'semester', 'tahun_akademik'])
                ->map(fn (Kelas $kelas) => [
                    'id' => $kelas->id,
                    'label' => "{$kelas->nama_kelas} - {$kelas->prodi} S{$kelas->semester} ({$kelas->tahun_akademik})",
                    'tahun_akademik' => $kelas->tahun_akademik,
                ]),
            'dosen' => Dosen::query()
                ->with('user:id,name,email')
                ->orderBy('nip')
                ->get()
                ->map(fn (Dosen $dosen) => [
                    'id' => $dosen->id,
                    'label' => "{$dosen->user?->name} - {$dosen->nip}",
                ]),
            'ruangan' => Ruangan::query()
                ->where('is_active', true)
                ->orderBy('nama')
                ->get(['id', 'nama', 'keterangan'])
                ->map(fn (Ruangan $ruangan) => [
                    'id' => $ruangan->id,
                    'nama' => $ruangan->nama,
                    'label' => $ruangan->keterangan
                        ? "{$ruangan->nama} - {$ruangan->keterangan}"
                        : $ruangan->nama,
                ]),
            'tahun_akademik' => Kelas::query()
                ->select('tahun_akademik')
                ->distinct()
                ->orderByDesc('tahun_akademik')
                ->pluck('tahun_akademik'),
        ];
    }

    private function formatTime(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        if (preg_match('/(\d{2}:\d{2})/', (string) $value, $matches)) {
            return $matches[1];
        }

        return (string) $value;
    }
}
