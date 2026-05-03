<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Mahasiswa;
use App\Models\Presensi;
use App\Models\SesiAbsensi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiwayatController extends Controller
{
    public function index(Request $request): Response
    {
        $mahasiswa = $this->currentMahasiswa($request);
        $kelasIds = $mahasiswa->kelas()->pluck('kelas.id');

        $history = Presensi::query()
            ->with(['sesiAbsensi.jadwal.kelas:id,nama_kelas,prodi', 'sesiAbsensi.jadwal.dosen.user:id,name'])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('sesiAbsensi.jadwal', fn ($query) => $query->whereIn('kelas_id', $kelasIds))
            ->latest('timestamp')
            ->paginate(15)
            ->through(fn (Presensi $presensi) => [
                'id' => $presensi->id,
                'tanggal' => $presensi->sesiAbsensi?->tanggal?->format('Y-m-d'),
                'mata_kuliah' => $presensi->sesiAbsensi?->jadwal?->mata_kuliah,
                'kelas' => $presensi->sesiAbsensi?->jadwal?->kelas?->nama_kelas,
                'dosen' => $presensi->sesiAbsensi?->jadwal?->dosen?->user?->name,
                'status' => $presensi->status,
                'timestamp' => $presensi->timestamp?->format('H:i'),
                'metode' => $presensi->metode,
            ]);

        return Inertia::render('Mahasiswa/Riwayat/Index', [
            'history' => $history,
            'summaries' => $this->courseSummaries($mahasiswa, $kelasIds),
        ]);
    }

    private function courseSummaries(Mahasiswa $mahasiswa, $kelasIds)
    {
        $courses = Jadwal::query()
            ->whereIn('kelas_id', $kelasIds)
            ->get(['id', 'mata_kuliah', 'kelas_id']);

        return $courses
            ->groupBy('mata_kuliah')
            ->map(function ($items, string $course) use ($mahasiswa) {
                $jadwalIds = $items->pluck('id');
                $totalSessions = SesiAbsensi::query()
                    ->whereIn('jadwal_id', $jadwalIds)
                    ->count();
                $presenceCounts = Presensi::query()
                    ->where('mahasiswa_id', $mahasiswa->id)
                    ->whereHas('sesiAbsensi', fn ($query) => $query->whereIn('jadwal_id', $jadwalIds))
                    ->selectRaw('status, count(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status');
                $hadir = (int) ($presenceCounts[Presensi::STATUS_HADIR] ?? 0);

                return [
                    'mata_kuliah' => $course,
                    'total_sesi' => $totalSessions,
                    'hadir' => $hadir,
                    'tidak_hadir' => (int) ($presenceCounts[Presensi::STATUS_TIDAK_HADIR] ?? 0),
                    'izin' => (int) ($presenceCounts[Presensi::STATUS_IZIN] ?? 0),
                    'sakit' => (int) ($presenceCounts[Presensi::STATUS_SAKIT] ?? 0),
                    'persentase' => $totalSessions > 0 ? round(($hadir / $totalSessions) * 100, 2) : 0,
                ];
            })
            ->values();
    }

    private function currentMahasiswa(Request $request): Mahasiswa
    {
        $mahasiswa = $request->user()?->mahasiswa;

        abort_unless($mahasiswa, 403);

        return $mahasiswa;
    }
}
