<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Mahasiswa;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JadwalController extends Controller
{
    public function index(Request $request): Response
    {
        $mahasiswa = $this->currentMahasiswa($request);
        $today = CarbonImmutable::today();
        $todayName = $this->indonesianDayName($today);
        $todayOrder = $this->dayOrder($todayName);
        $kelasIds = $mahasiswa->kelas()->pluck('kelas.id');

        $schedules = Jadwal::query()
            ->with(['kelas:id,nama_kelas,prodi', 'dosen.user:id,name'])
            ->whereIn('kelas_id', $kelasIds)
            ->get()
            ->sortBy([
                fn (Jadwal $jadwal) => $this->relativeDayOrder($jadwal->hari, $todayOrder),
                fn (Jadwal $jadwal) => $this->formatTime($jadwal->jam_mulai) ?? '',
            ])
            ->values()
            ->map(fn (Jadwal $jadwal) => $this->formatJadwal($jadwal, $todayName));

        return Inertia::render('Mahasiswa/Jadwal/Index', [
            'todayName' => $todayName,
            'schedules' => $schedules,
        ]);
    }

    private function currentMahasiswa(Request $request): Mahasiswa
    {
        $mahasiswa = $request->user()?->mahasiswa;

        abort_unless($mahasiswa, 403);

        return $mahasiswa;
    }

    private function formatJadwal(Jadwal $jadwal, string $todayName): array
    {
        $status = $this->scheduleTemporalStatus($jadwal);

        return [
            'id' => $jadwal->id,
            'mata_kuliah' => $jadwal->mata_kuliah,
            'hari' => $jadwal->hari,
            'is_today' => $jadwal->hari === $todayName,
            'schedule_status' => $status['code'],
            'schedule_status_label' => $status['label'],
            'schedule_status_description' => $status['description'],
            'jam_mulai' => $this->formatTime($jadwal->jam_mulai),
            'jam_selesai' => $this->formatTime($jadwal->jam_selesai),
            'ruangan' => $jadwal->ruangan,
            'dosen' => $jadwal->dosen?->user?->name,
            'kelas' => $jadwal->kelas ? [
                'id' => $jadwal->kelas->id,
                'nama_kelas' => $jadwal->kelas->nama_kelas,
                'prodi' => $jadwal->kelas->prodi,
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

    /**
     * @return array{code: string, label: string, description: ?string}
     */
    private function scheduleTemporalStatus(Jadwal $jadwal): array
    {
        $now = CarbonImmutable::now();
        $todayOrder = $this->dayOrder($this->indonesianDayName($now));
        $scheduleOrder = $this->dayOrder($jadwal->hari);

        if ($scheduleOrder < $todayOrder) {
            return [
                'code' => 'ended',
                'label' => 'Telah berakhir',
                'description' => 'Jadwal minggu ini sudah berakhir.',
            ];
        }

        if ($scheduleOrder > $todayOrder) {
            return [
                'code' => 'upcoming',
                'label' => 'Belum dimulai',
                'description' => "Jadwal berlangsung pada hari {$jadwal->hari}.",
            ];
        }

        $start = $this->scheduledDateTime($now, $jadwal->jam_mulai);
        $end = $this->scheduledDateTime($now, $jadwal->jam_selesai);

        if ($start !== null && $end !== null && $now->betweenIncluded($start, $end)) {
            return [
                'code' => 'ongoing',
                'label' => 'Sedang berlangsung',
                'description' => null,
            ];
        }

        if ($start !== null && $now->lessThan($start)) {
            return [
                'code' => 'upcoming',
                'label' => 'Belum dimulai',
                'description' => sprintf(
                    'Jadwal dimulai pukul %s.',
                    $this->formatTime($jadwal->jam_mulai) ?? '-',
                ),
            ];
        }

        if ($end !== null && $now->greaterThan($end)) {
            return [
                'code' => 'ended',
                'label' => 'Telah berakhir',
                'description' => 'Jadwal hari ini telah berakhir.',
            ];
        }

        return [
            'code' => 'unavailable',
            'label' => 'Tidak tersedia',
            'description' => null,
        ];
    }

    private function scheduledDateTime(CarbonImmutable $date, mixed $time): ?CarbonImmutable
    {
        $formattedTime = $this->formatTime($time);

        if (! $formattedTime) {
            return null;
        }

        return $date->setTimeFromTimeString($formattedTime);
    }

    private function indonesianDayName(CarbonImmutable $date): string
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][$date->dayOfWeekIso];
    }

    private function dayOrder(string $day): int
    {
        return array_search($day, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'], true) ?: 0;
    }

    private function relativeDayOrder(string $day, int $todayOrder): int
    {
        $order = $this->dayOrder($day);

        return $order >= $todayOrder ? $order - $todayOrder : $order + 7 - $todayOrder;
    }
}
