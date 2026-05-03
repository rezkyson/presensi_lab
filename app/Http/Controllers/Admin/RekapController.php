<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ArrayReportExport;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Presensi;
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
        $filters = $this->filters($request);
        $query = $this->reportQuery($filters);
        $stats = $this->statusSummary(clone $query);

        $records = $query
            ->latest('timestamp')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Presensi $presensi) => $this->formatPresensi($presensi));

        return Inertia::render('Admin/Rekap/Index', [
            'filters' => $filters,
            'records' => $records,
            'stats' => $stats,
            'options' => [
                'kelas' => Kelas::query()->orderBy('nama_kelas')->get(['id', 'nama_kelas', 'prodi']),
                'dosen' => Dosen::query()->with('user:id,name')->get(['id', 'user_id', 'nip'])
                    ->map(fn (Dosen $dosen) => [
                        'id' => $dosen->id,
                        'name' => $dosen->user?->name,
                        'nip' => $dosen->nip,
                    ]),
                'mahasiswa' => Mahasiswa::query()->with('user:id,name')->get(['id', 'user_id', 'nim'])
                    ->map(fn (Mahasiswa $mahasiswa) => [
                        'id' => $mahasiswa->id,
                        'name' => $mahasiswa->user?->name,
                        'nim' => $mahasiswa->nim,
                    ]),
            ],
        ]);
    }

    public function exportPdf(Request $request)
    {
        [$headings, $rows] = $this->exportRows($request);

        return Pdf::loadView('exports.rekap', [
            'title' => 'Rekap Presensi Admin',
            'summary' => $this->statusSummary($this->reportQuery($this->filters($request))),
            'headings' => $headings,
            'rows' => $rows,
        ])->download('rekap-presensi-admin.pdf');
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        [$headings, $rows] = $this->exportRows($request);

        return Excel::download(new ArrayReportExport($headings, $rows), 'rekap-presensi-admin.xlsx');
    }

    private function reportQuery(array $filters): Builder
    {
        return Presensi::query()
            ->with([
                'mahasiswa.user:id,name,email',
                'sesiAbsensi.jadwal.kelas:id,nama_kelas,prodi',
                'sesiAbsensi.jadwal.dosen.user:id,name',
            ])
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['mahasiswa_id'], fn (Builder $query, string $id) => $query->where('mahasiswa_id', $id))
            ->when($filters['date_from'], fn (Builder $query, string $date) => $query->whereHas(
                'sesiAbsensi',
                fn (Builder $subQuery) => $subQuery->whereDate('tanggal', '>=', $date),
            ))
            ->when($filters['date_to'], fn (Builder $query, string $date) => $query->whereHas(
                'sesiAbsensi',
                fn (Builder $subQuery) => $subQuery->whereDate('tanggal', '<=', $date),
            ))
            ->whereHas('sesiAbsensi.jadwal', function (Builder $query) use ($filters) {
                $query
                    ->when($filters['kelas_id'], fn (Builder $subQuery, string $id) => $subQuery->where('kelas_id', $id))
                    ->when($filters['dosen_id'], fn (Builder $subQuery, string $id) => $subQuery->where('dosen_id', $id))
                    ->when($filters['mata_kuliah'], fn (Builder $subQuery, string $value) => $subQuery->where('mata_kuliah', 'like', "%{$value}%"));
            });
    }

    private function filters(Request $request): array
    {
        return [
            'kelas_id' => $request->string('kelas_id')->toString() ?: null,
            'mahasiswa_id' => $request->string('mahasiswa_id')->toString() ?: null,
            'dosen_id' => $request->string('dosen_id')->toString() ?: null,
            'mata_kuliah' => $request->string('mata_kuliah')->toString() ?: null,
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ];
    }

    private function statusSummary(Builder $query): array
    {
        $counts = $query->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'hadir' => (int) ($counts[Presensi::STATUS_HADIR] ?? 0),
            'tidak_hadir' => (int) ($counts[Presensi::STATUS_TIDAK_HADIR] ?? 0),
            'izin' => (int) ($counts[Presensi::STATUS_IZIN] ?? 0),
            'sakit' => (int) ($counts[Presensi::STATUS_SAKIT] ?? 0),
            'total' => (int) $counts->sum(),
        ];
    }

    private function exportRows(Request $request): array
    {
        $headings = ['Tanggal', 'Mahasiswa', 'NIM', 'Kelas', 'Dosen', 'Mata Kuliah', 'Status', 'Waktu', 'Metode'];
        $rows = $this->reportQuery($this->filters($request))
            ->latest('timestamp')
            ->get()
            ->map(fn (Presensi $presensi) => [
                $presensi->sesiAbsensi?->tanggal?->format('Y-m-d'),
                $presensi->mahasiswa?->user?->name,
                $presensi->mahasiswa?->nim,
                $presensi->sesiAbsensi?->jadwal?->kelas?->nama_kelas,
                $presensi->sesiAbsensi?->jadwal?->dosen?->user?->name,
                $presensi->sesiAbsensi?->jadwal?->mata_kuliah,
                $presensi->status,
                $presensi->timestamp?->format('H:i'),
                $presensi->metode,
            ]);

        return [$headings, $rows];
    }

    private function formatPresensi(Presensi $presensi): array
    {
        return [
            'id' => $presensi->id,
            'tanggal' => $presensi->sesiAbsensi?->tanggal?->format('Y-m-d'),
            'mahasiswa' => $presensi->mahasiswa?->user?->name,
            'nim' => $presensi->mahasiswa?->nim,
            'kelas' => $presensi->sesiAbsensi?->jadwal?->kelas?->nama_kelas,
            'dosen' => $presensi->sesiAbsensi?->jadwal?->dosen?->user?->name,
            'mata_kuliah' => $presensi->sesiAbsensi?->jadwal?->mata_kuliah,
            'status' => $presensi->status,
            'timestamp' => $presensi->timestamp?->format('H:i'),
            'metode' => $presensi->metode,
        ];
    }
}
