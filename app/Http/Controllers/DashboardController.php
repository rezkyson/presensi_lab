<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Presensi;
use App\Models\SesiAbsensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Welcome', [
            'appName' => config('app.name', 'Sistem Kehadiran Digital'),
        ]);
    }

    public function redirect(Request $request): RedirectResponse
    {
        return match ($request->user()->role) {
            User::ROLE_ADMIN => redirect()->route('admin.dashboard'),
            User::ROLE_DOSEN => redirect()->route('dosen.dashboard'),
            User::ROLE_MAHASISWA => redirect()->route('mahasiswa.dashboard'),
            default => redirect('/'),
        };
    }

    public function admin(): Response
    {
        $today = CarbonImmutable::today();

        $attendanceToday = Presensi::query()
            ->whereHas('sesiAbsensi', fn ($query) => $query->whereDate('tanggal', $today))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'mahasiswa' => Mahasiswa::count(),
                'dosen' => Dosen::count(),
                'kelas' => Kelas::count(),
                'jadwalAktif' => Jadwal::count(),
            ],
            'attendanceToday' => [
                'hadir' => (int) ($attendanceToday[Presensi::STATUS_HADIR] ?? 0),
                'tidak_hadir' => (int) ($attendanceToday[Presensi::STATUS_TIDAK_HADIR] ?? 0),
                'izin' => (int) ($attendanceToday[Presensi::STATUS_IZIN] ?? 0),
                'sakit' => (int) ($attendanceToday[Presensi::STATUS_SAKIT] ?? 0),
            ],
            'recentSchedules' => Jadwal::query()
                ->with(['kelas:id,nama_kelas,prodi', 'dosen.user:id,name'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (Jadwal $jadwal) => $this->formatJadwal($jadwal)),
        ]);
    }

    public function dosen(): Response
    {
        $dosen = request()->user()->dosen;
        $today = CarbonImmutable::today();
        $todayName = $this->indonesianDayName($today);
        $todayOrder = $this->dayOrder($todayName);

        $jadwal = Jadwal::query()
            ->with([
                'kelas:id,nama_kelas,prodi',
                'sesiAbsensi' => fn ($query) => $query
                    ->whereDate('tanggal', $today)
                    ->latest('dibuka_at'),
            ])
            ->where('dosen_id', $dosen?->id)
            ->get();

        return Inertia::render('Dosen/Dashboard', [
            'todayName' => $todayName,
            'todaySchedules' => $jadwal
                ->where('hari', $todayName)
                ->sortBy('jam_mulai')
                ->values()
                ->map(fn (Jadwal $jadwal) => $this->formatJadwal($jadwal)),
            'upcomingSchedules' => $jadwal
                ->filter(fn (Jadwal $jadwal) => $this->dayOrder($jadwal->hari) > $todayOrder)
                ->sortBy([
                    fn (Jadwal $jadwal) => $this->dayOrder($jadwal->hari),
                    fn (Jadwal $jadwal) => $this->formatTime($jadwal->jam_mulai) ?? '',
                ])
                ->take(5)
                ->values()
                ->map(fn (Jadwal $jadwal) => $this->formatJadwal($jadwal)),
            'activeSessions' => SesiAbsensi::query()
                ->with('jadwal.kelas:id,nama_kelas,prodi')
                ->where('dosen_id', $dosen?->id)
                ->where('status', SesiAbsensi::STATUS_AKTIF)
                ->whereDate('tanggal', $today)
                ->latest('dibuka_at')
                ->get()
                ->map(fn (SesiAbsensi $sesi) => [
                    'id' => $sesi->id,
                    'mata_kuliah' => $sesi->jadwal?->mata_kuliah,
                    'kelas' => $sesi->jadwal?->kelas?->nama_kelas,
                    'dibuka_at' => $sesi->dibuka_at?->format('H:i'),
                ]),
        ]);
    }

    public function mahasiswa(): Response
    {
        $mahasiswa = request()->user()->mahasiswa;
        $today = CarbonImmutable::today();
        $todayName = $this->indonesianDayName($today);
        $kelasIds = $mahasiswa?->kelas()->pluck('kelas.id') ?? collect();

        $todaySchedules = Jadwal::query()
            ->with(['kelas:id,nama_kelas,prodi', 'dosen.user:id,name'])
            ->whereIn('kelas_id', $kelasIds)
            ->where('hari', $todayName)
            ->orderBy('jam_mulai')
            ->get();

        $attendanceToday = Presensi::query()
            ->with('sesiAbsensi.jadwal:id,mata_kuliah')
            ->where('mahasiswa_id', $mahasiswa?->id)
            ->whereHas('sesiAbsensi', fn ($query) => $query->whereDate('tanggal', $today))
            ->get()
            ->map(fn (Presensi $presensi) => [
                'id' => $presensi->id,
                'mata_kuliah' => $presensi->sesiAbsensi?->jadwal?->mata_kuliah,
                'status' => $presensi->status,
                'timestamp' => $presensi->timestamp?->format('H:i'),
            ]);

        $activeSession = SesiAbsensi::query()
            ->where('status', SesiAbsensi::STATUS_AKTIF)
            ->whereDate('tanggal', $today)
            ->whereHas('jadwal', fn ($query) => $query->whereIn('kelas_id', $kelasIds))
            ->whereDoesntHave('presensi', fn ($query) => $query->where('mahasiswa_id', $mahasiswa?->id))
            ->with('jadwal.kelas:id,nama_kelas')
            ->first();

        return Inertia::render('Mahasiswa/Dashboard', [
            'todayName' => $todayName,
            'faceRegistered' => (bool) $mahasiswa?->wajah_terdaftar,
            'todaySchedules' => $todaySchedules->map(fn (Jadwal $jadwal) => $this->formatJadwal($jadwal)),
            'attendanceToday' => $attendanceToday,
            'availableAttendanceSession' => $activeSession ? [
                'id' => $activeSession->id,
                'mata_kuliah' => $activeSession->jadwal?->mata_kuliah,
                'kelas' => $activeSession->jadwal?->kelas?->nama_kelas,
            ] : null,
        ]);
    }

    private function formatJadwal(Jadwal $jadwal): array
    {
        $sessions = $jadwal->relationLoaded('sesiAbsensi') ? $jadwal->sesiAbsensi : collect();
        $activeSession = $sessions->firstWhere('status', SesiAbsensi::STATUS_AKTIF);
        $completedSession = $sessions->firstWhere('status', SesiAbsensi::STATUS_SELESAI);
        $isClosedToday = $completedSession !== null;
        $status = $this->scheduleTemporalStatus($jadwal);

        return [
            'id' => $jadwal->id,
            'mata_kuliah' => $jadwal->mata_kuliah,
            'hari' => $jadwal->hari,
            'jam_mulai' => $this->formatTime($jadwal->jam_mulai),
            'jam_selesai' => $this->formatTime($jadwal->jam_selesai),
            'ruangan' => $jadwal->ruangan,
            'kelas' => $jadwal->kelas ? [
                'id' => $jadwal->kelas->id,
                'nama_kelas' => $jadwal->kelas->nama_kelas,
                'prodi' => $jadwal->kelas->prodi,
            ] : null,
            'dosen' => $jadwal->dosen?->user?->name,
            'schedule_status' => $status['code'],
            'schedule_status_label' => $isClosedToday ? 'Sesi selesai' : $status['label'],
            'schedule_status_description' => $isClosedToday ? 'Sesi hari ini sudah ditutup.' : $status['description'],
            'can_open_session' => ! $isClosedToday && $status['code'] === 'ongoing',
            'unavailable_reason' => $isClosedToday
                ? 'Sesi hari ini sudah ditutup.'
                : $status['description'],
            'active_session_id' => $activeSession?->id,
            'completed_session_id' => $completedSession?->id,
            'closed_at' => $completedSession?->ditutup_at?->format('H:i'),
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

    private function canOpenScheduleNow(Jadwal $jadwal): bool
    {
        return $this->scheduleTemporalStatus($jadwal)['code'] === 'ongoing';
    }

    private function scheduleUnavailableReason(Jadwal $jadwal): ?string
    {
        return $this->scheduleTemporalStatus($jadwal)['description'];
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
                'description' => "Sesi hanya bisa dibuka pada hari {$jadwal->hari}.",
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
                    'Sesi belum dimulai. Jadwal dibuka pukul %s-%s.',
                    $this->formatTime($jadwal->jam_mulai) ?? '-',
                    $this->formatTime($jadwal->jam_selesai) ?? '-',
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
            'description' => sprintf(
                'Sesi hanya bisa dibuka pada pukul %s-%s.',
                $this->formatTime($jadwal->jam_mulai) ?? '-',
                $this->formatTime($jadwal->jam_selesai) ?? '-',
            ),
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
}
