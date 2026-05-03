<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Presensi;
use App\Models\SesiAbsensi;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonitorPresensiController extends Controller
{
    public function index(Request $request): Response
    {
        $dosen = $this->currentDosen($request);

        $sessions = SesiAbsensi::query()
            ->with(['jadwal.kelas:id,nama_kelas,prodi'])
            ->where('dosen_id', $dosen->id)
            ->latest('dibuka_at')
            ->limit(20)
            ->get()
            ->map(fn (SesiAbsensi $sesi) => $this->formatSession($sesi));

        return Inertia::render('Dosen/Monitor/Index', [
            'sessions' => $sessions,
        ]);
    }

    public function show(Request $request, SesiAbsensi $sesi): Response
    {
        $this->authorizeSession($request, $sesi);

        $sesi->load(['jadwal.kelas:id,nama_kelas,prodi,semester,tahun_akademik']);

        return Inertia::render('Dosen/Monitor/Show', [
            'session' => $this->formatSession($sesi),
            'attendance' => $this->attendanceData($sesi),
            'pollingMs' => 5000,
            'broadcastingEnabled' => filled(config('broadcasting.default'))
                && config('broadcasting.default') !== 'null',
        ]);
    }

    public function attendance(Request $request, SesiAbsensi $sesi): JsonResponse
    {
        $this->authorizeSession($request, $sesi);

        return response()->json([
            'attendance' => $this->attendanceData($sesi),
        ]);
    }

    private function attendanceData(SesiAbsensi $sesi): array
    {
        $sesi->load([
            'jadwal.kelas.mahasiswa.user:id,name,email',
            'presensi.mahasiswa.user:id,name,email',
        ]);

        $presensiByMahasiswa = $sesi->presensi->keyBy('mahasiswa_id');
        $participants = $sesi->jadwal?->kelas?->mahasiswa ?? collect();

        $rows = $participants
            ->sortBy(fn ($mahasiswa) => $mahasiswa->user?->name ?? $mahasiswa->nim)
            ->values()
            ->map(function ($mahasiswa) use ($presensiByMahasiswa) {
                $presensi = $presensiByMahasiswa->get($mahasiswa->id);

                return [
                    'mahasiswa_id' => $mahasiswa->id,
                    'nama' => $mahasiswa->user?->name,
                    'nim' => $mahasiswa->nim,
                    'status' => $presensi?->status ?? 'belum_hadir',
                    'timestamp' => $presensi?->timestamp?->format('H:i'),
                    'metode' => $presensi?->metode,
                ];
            });

        return [
            'summary' => [
                'total' => $participants->count(),
                'hadir' => $rows->where('status', Presensi::STATUS_HADIR)->count(),
                'belum_hadir' => $rows->where('status', 'belum_hadir')->count(),
                'tidak_hadir' => $rows->where('status', Presensi::STATUS_TIDAK_HADIR)->count(),
                'izin' => $rows->where('status', Presensi::STATUS_IZIN)->count(),
                'sakit' => $rows->where('status', Presensi::STATUS_SAKIT)->count(),
            ],
            'participants' => $rows,
            'last_updated_at' => now()->toIso8601String(),
        ];
    }

    private function currentDosen(Request $request): Dosen
    {
        $dosen = $request->user()?->dosen;

        abort_unless($dosen, 403);

        return $dosen;
    }

    private function authorizeSession(Request $request, SesiAbsensi $sesi): void
    {
        abort_unless($sesi->dosen_id === $this->currentDosen($request)->id, 403);
    }

    private function formatSession(SesiAbsensi $sesi): array
    {
        return [
            'id' => $sesi->id,
            'status' => $sesi->status,
            'tanggal' => $sesi->tanggal?->format('Y-m-d'),
            'dibuka_at' => $sesi->dibuka_at?->format('H:i'),
            'ditutup_at' => $sesi->ditutup_at?->format('H:i'),
            'mata_kuliah' => $sesi->jadwal?->mata_kuliah,
            'hari' => $sesi->jadwal?->hari,
            'jam_mulai' => $this->formatTime($sesi->jadwal?->jam_mulai),
            'jam_selesai' => $this->formatTime($sesi->jadwal?->jam_selesai),
            'ruangan' => $sesi->jadwal?->ruangan,
            'kelas' => $sesi->jadwal?->kelas ? [
                'id' => $sesi->jadwal->kelas->id,
                'nama_kelas' => $sesi->jadwal->kelas->nama_kelas,
                'prodi' => $sesi->jadwal->kelas->prodi,
                'semester' => $sesi->jadwal->kelas->semester,
                'tahun_akademik' => $sesi->jadwal->kelas->tahun_akademik,
            ] : null,
        ];
    }

    private function formatTime(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        if (preg_match('/(\d{2}:\d{2})/', (string) $value, $matches)) {
            return $matches[1];
        }

        return (string) $value;
    }
}
