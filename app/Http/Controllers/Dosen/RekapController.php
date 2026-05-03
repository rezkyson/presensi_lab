<?php

namespace App\Http\Controllers\Dosen;

use App\Exports\ArrayReportExport;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Presensi;
use App\Models\SesiAbsensi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RekapController extends Controller
{
    public function index(Request $request): Response
    {
        $dosen = $this->currentDosen($request);
        $filters = $this->filters($request);

        $sessions = $this->sessionQuery($dosen, $filters)
            ->latest('tanggal')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (SesiAbsensi $sesi) => $this->formatSessionSummary($sesi));

        return Inertia::render('Dosen/Rekap/Index', [
            'filters' => $filters,
            'sessions' => $sessions,
            'options' => [
                'kelas' => Kelas::query()
                    ->whereHas('jadwal', fn (Builder $query) => $query->where('dosen_id', $dosen->id))
                    ->orderBy('nama_kelas')
                    ->get(['id', 'nama_kelas', 'prodi']),
            ],
        ]);
    }

    public function exportPdf(Request $request)
    {
        [$headings, $rows] = $this->exportRows($request);

        return Pdf::loadView('exports.rekap', [
            'title' => 'Rekap Presensi Dosen',
            'summary' => [],
            'headings' => $headings,
            'rows' => $rows,
        ])->download('rekap-presensi-dosen.pdf');
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        [$headings, $rows] = $this->exportRows($request);

        return Excel::download(new ArrayReportExport($headings, $rows), 'rekap-presensi-dosen.xlsx');
    }

    private function sessionQuery(Dosen $dosen, array $filters): Builder
    {
        return SesiAbsensi::query()
            ->with(['jadwal.kelas:id,nama_kelas,prodi', 'presensi'])
            ->where('dosen_id', $dosen->id)
            ->when($filters['date_from'], fn (Builder $query, string $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['date_to'], fn (Builder $query, string $date) => $query->whereDate('tanggal', '<=', $date))
            ->whereHas('jadwal', function (Builder $query) use ($filters) {
                $query
                    ->when($filters['kelas_id'], fn (Builder $subQuery, string $id) => $subQuery->where('kelas_id', $id))
                    ->when($filters['mata_kuliah'], fn (Builder $subQuery, string $value) => $subQuery->where('mata_kuliah', 'like', "%{$value}%"));
            });
    }

    private function filters(Request $request): array
    {
        return [
            'kelas_id' => $request->string('kelas_id')->toString() ?: null,
            'mata_kuliah' => $request->string('mata_kuliah')->toString() ?: null,
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
        ];
    }

    private function exportRows(Request $request): array
    {
        $dosen = $this->currentDosen($request);
        $headings = ['Tanggal', 'Kelas', 'Mata Kuliah', 'Ruangan', 'Status Sesi', 'Hadir', 'Tidak Hadir', 'Izin', 'Sakit', 'Total'];
        $rows = $this->sessionQuery($dosen, $this->filters($request))
            ->latest('tanggal')
            ->get()
            ->map(function (SesiAbsensi $sesi) {
                $summary = $this->presenceSummary($sesi);

                return [
                    $sesi->tanggal?->format('Y-m-d'),
                    $sesi->jadwal?->kelas?->nama_kelas,
                    $sesi->jadwal?->mata_kuliah,
                    $sesi->jadwal?->ruangan,
                    $sesi->status,
                    $summary['hadir'],
                    $summary['tidak_hadir'],
                    $summary['izin'],
                    $summary['sakit'],
                    $summary['total'],
                ];
            });

        return [$headings, $rows];
    }

    private function formatSessionSummary(SesiAbsensi $sesi): array
    {
        return [
            'id' => $sesi->id,
            'tanggal' => $sesi->tanggal?->format('Y-m-d'),
            'status' => $sesi->status,
            'dibuka_at' => $sesi->dibuka_at?->format('H:i'),
            'ditutup_at' => $sesi->ditutup_at?->format('H:i'),
            'kelas' => $sesi->jadwal?->kelas?->nama_kelas,
            'prodi' => $sesi->jadwal?->kelas?->prodi,
            'mata_kuliah' => $sesi->jadwal?->mata_kuliah,
            'ruangan' => $sesi->jadwal?->ruangan,
            'summary' => $this->presenceSummary($sesi),
        ];
    }

    private function presenceSummary(SesiAbsensi $sesi): array
    {
        $counts = $sesi->presensi
            ->groupBy('status')
            ->map(fn ($items) => $items->count());

        return [
            'hadir' => (int) ($counts[Presensi::STATUS_HADIR] ?? 0),
            'tidak_hadir' => (int) ($counts[Presensi::STATUS_TIDAK_HADIR] ?? 0),
            'izin' => (int) ($counts[Presensi::STATUS_IZIN] ?? 0),
            'sakit' => (int) ($counts[Presensi::STATUS_SAKIT] ?? 0),
            'total' => (int) $sesi->presensi->count(),
        ];
    }

    private function currentDosen(Request $request): Dosen
    {
        $dosen = $request->user()?->dosen;

        abort_unless($dosen, 403);

        return $dosen;
    }
}
