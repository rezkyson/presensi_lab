<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Presensi;
use App\Models\QrToken;
use App\Models\SesiAbsensi;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SesiAbsensiController extends Controller
{
    public function index(Request $request): Response
    {
        $dosen = $this->currentDosen($request);
        $today = CarbonImmutable::today();
        $todayName = $this->indonesianDayName($today);

        $jadwal = Jadwal::query()
            ->with([
                'kelas:id,nama_kelas,prodi,semester,tahun_akademik',
                'sesiAbsensi' => fn ($query) => $query
                    ->whereDate('tanggal', $today)
                    ->where('status', SesiAbsensi::STATUS_AKTIF),
            ])
            ->where('dosen_id', $dosen->id)
            ->get()
            ->sortBy([
                fn (Jadwal $jadwal) => $this->dayOrder($jadwal->hari),
                fn (Jadwal $jadwal) => $this->formatTime($jadwal->jam_mulai) ?? '',
            ])
            ->values();

        $activeSessions = SesiAbsensi::query()
            ->with(['jadwal.kelas:id,nama_kelas,prodi'])
            ->where('dosen_id', $dosen->id)
            ->where('status', SesiAbsensi::STATUS_AKTIF)
            ->latest('dibuka_at')
            ->get()
            ->map(fn (SesiAbsensi $sesi) => $this->formatSession($sesi));

        return Inertia::render('Dosen/Sesi/Index', [
            'todayName' => $todayName,
            'schedules' => $jadwal->map(fn (Jadwal $jadwal) => $this->formatJadwal($jadwal)),
            'activeSessions' => $activeSessions,
        ]);
    }

    public function store(Request $request, Jadwal $jadwal): RedirectResponse
    {
        $dosen = $this->currentDosen($request);

        abort_unless($jadwal->dosen_id === $dosen->id, 403);

        $sesi = DB::transaction(function () use ($dosen, $jadwal) {
            $sesi = SesiAbsensi::query()->firstOrCreate(
                [
                    'jadwal_id' => $jadwal->id,
                    'tanggal' => CarbonImmutable::today()->toDateString(),
                    'status' => SesiAbsensi::STATUS_AKTIF,
                ],
                [
                    'dosen_id' => $dosen->id,
                    'dibuka_at' => now(),
                ],
            );

            $this->createQrToken($sesi);

            return $sesi;
        });

        return redirect()
            ->route('dosen.sesi.qr', $sesi)
            ->with('success', 'Sesi absensi berhasil dibuka.');
    }

    public function qr(Request $request, SesiAbsensi $sesi): Response
    {
        $this->authorizeSession($request, $sesi);

        abort_unless($sesi->status === SesiAbsensi::STATUS_AKTIF, 404);

        $sesi->load('jadwal.kelas:id,nama_kelas,prodi,semester,tahun_akademik');
        $token = $sesi->qrTokens()
            ->where('expired_at', '>', now())
            ->latest()
            ->first() ?? $this->createQrToken($sesi);

        return Inertia::render('Dosen/Sesi/Qr', [
            'session' => $this->formatSession($sesi),
            'qr' => $this->formatQrToken($token),
            'refreshSeconds' => 60,
        ]);
    }

    public function qrData(Request $request, SesiAbsensi $sesi): JsonResponse
    {
        $this->authorizeSession($request, $sesi);

        if ($sesi->status !== SesiAbsensi::STATUS_AKTIF) {
            return response()->json([
                'message' => 'Sesi absensi sudah ditutup.',
            ], 409);
        }

        return response()->json([
            'qr' => $this->formatQrToken($this->createQrToken($sesi)),
        ]);
    }

    public function destroy(Request $request, SesiAbsensi $sesi): RedirectResponse
    {
        $this->authorizeSession($request, $sesi);

        DB::transaction(function () use ($sesi) {
            $sesi->load('jadwal.kelas.mahasiswa:id');

            foreach ($sesi->jadwal?->kelas?->mahasiswa ?? collect() as $mahasiswa) {
                Presensi::query()->firstOrCreate(
                    [
                        'sesi_id' => $sesi->id,
                        'mahasiswa_id' => $mahasiswa->id,
                    ],
                    [
                        'status' => Presensi::STATUS_TIDAK_HADIR,
                        'timestamp' => now(),
                        'metode' => 'auto_close',
                    ],
                );
            }

            $sesi->qrTokens()->delete();
            $sesi->update([
                'status' => SesiAbsensi::STATUS_SELESAI,
                'ditutup_at' => now(),
            ]);
        });

        return redirect()
            ->route('dosen.sesi.index')
            ->with('success', 'Sesi absensi berhasil ditutup.');
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

    private function createQrToken(SesiAbsensi $sesi): QrToken
    {
        $sesi->qrTokens()->delete();

        return $sesi->qrTokens()->create([
            'token' => Str::random(64),
            'expired_at' => now()->addSeconds(60),
            'used_count' => 0,
        ]);
    }

    private function formatQrToken(QrToken $token): array
    {
        $payload = json_encode([
            'type' => 'sihadir_attendance',
            'sesi_id' => $token->sesi_id,
            'token' => $token->token,
        ], JSON_THROW_ON_ERROR);

        $builder = new Builder(
            writer: new PngWriter,
            writerOptions: [],
            validateResult: false,
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 520,
            margin: 16,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        return [
            'token' => $token->token,
            'payload' => $payload,
            'data_uri' => $builder->build()->getDataUri(),
            'expired_at' => $token->expired_at?->toIso8601String(),
            'expires_in' => max(0, now()->diffInSeconds($token->expired_at, false)),
        ];
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

    private function formatJadwal(Jadwal $jadwal): array
    {
        $activeSession = $jadwal->sesiAbsensi->first();

        return [
            'id' => $jadwal->id,
            'mata_kuliah' => $jadwal->mata_kuliah,
            'hari' => $jadwal->hari,
            'jam_mulai' => $this->formatTime($jadwal->jam_mulai),
            'jam_selesai' => $this->formatTime($jadwal->jam_selesai),
            'ruangan' => $jadwal->ruangan,
            'is_today' => $jadwal->hari === $this->indonesianDayName(CarbonImmutable::today()),
            'active_session_id' => $activeSession?->id,
            'kelas' => $jadwal->kelas ? [
                'id' => $jadwal->kelas->id,
                'nama_kelas' => $jadwal->kelas->nama_kelas,
                'prodi' => $jadwal->kelas->prodi,
                'semester' => $jadwal->kelas->semester,
                'tahun_akademik' => $jadwal->kelas->tahun_akademik,
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
