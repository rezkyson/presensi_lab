<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Events\MahasiswaHadir;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mahasiswa\VerifyFaceRequest;
use App\Http\Requests\Mahasiswa\VerifyQrRequest;
use App\Models\Mahasiswa;
use App\Models\Presensi;
use App\Models\QrToken;
use App\Models\SesiAbsensi;
use App\Services\SesiAbsensiFinalizer;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AbsensiController extends Controller
{
    private const FACE_THRESHOLD = 0.5;

    private const FACE_DESCRIPTOR_LENGTH = 128;

    private const MAX_FACE_ATTEMPTS = 3;

    private const LIVENESS_STEPS = ['mouth_open', 'turn_left'];

    public function __construct(private readonly SesiAbsensiFinalizer $finalizer)
    {
    }

    public function index(Request $request): Response
    {
        $mahasiswa = $this->currentMahasiswa($request);
        $today = CarbonImmutable::today();
        $kelasIds = $mahasiswa->kelas()->pluck('kelas.id');

        SesiAbsensi::query()
            ->with('jadwal')
            ->where('status', SesiAbsensi::STATUS_AKTIF)
            ->whereDate('tanggal', $today)
            ->whereHas('jadwal', fn ($query) => $query->whereIn('kelas_id', $kelasIds))
            ->get()
            ->each(fn (SesiAbsensi $sesi) => $this->finalizer->finalizeIfScheduleEnded($sesi));

        $activeSessions = SesiAbsensi::query()
            ->with(['jadwal.kelas:id,nama_kelas,prodi', 'jadwal.dosen.user:id,name'])
            ->where('status', SesiAbsensi::STATUS_AKTIF)
            ->whereDate('tanggal', $today)
            ->whereHas('jadwal', fn ($query) => $query->whereIn('kelas_id', $kelasIds))
            ->whereDoesntHave('presensi', fn ($query) => $query->where('mahasiswa_id', $mahasiswa->id))
            ->latest('dibuka_at')
            ->get()
            ->map(fn (SesiAbsensi $sesi) => $this->formatSession($sesi));

        $attendanceToday = Presensi::query()
            ->with('sesiAbsensi.jadwal:id,mata_kuliah')
            ->where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('sesiAbsensi', fn ($query) => $query->whereDate('tanggal', $today))
            ->latest('timestamp')
            ->get()
            ->map(fn (Presensi $presensi) => [
                'id' => $presensi->id,
                'mata_kuliah' => $presensi->sesiAbsensi?->jadwal?->mata_kuliah,
                'status' => $presensi->status,
                'timestamp' => $presensi->timestamp?->format('H:i'),
            ]);

        return Inertia::render('Mahasiswa/Absen/Index', [
            'faceRegistered' => (bool) $mahasiswa->wajah_terdaftar,
            'activeSessions' => $activeSessions,
            'attendanceToday' => $attendanceToday,
        ]);
    }

    public function verifyQr(VerifyQrRequest $request): JsonResponse
    {
        $mahasiswa = $this->currentMahasiswa($request);

        if (! $mahasiswa->wajah_terdaftar) {
            $this->logFailedAttendance($request, $mahasiswa, 'qr_face_not_registered');
            abort(403, 'Daftarkan wajah terlebih dahulu.');
        }

        $data = $request->validated();

        $payload = $this->parseQrPayload($request, $mahasiswa, $data['qr_payload']);

        $qrToken = QrToken::query()
            ->with('sesiAbsensi.jadwal.kelas')
            ->where('token', $payload['token'])
            ->where('sesi_id', $payload['sesi_id'])
            ->where('expired_at', '>', now())
            ->first();

        if (! $qrToken) {
            $this->invalidQr($request, $mahasiswa, 'Token QR tidak valid atau sudah kedaluwarsa.', [
                'payload_sesi_id' => $payload['sesi_id'],
            ]);
        }

        $sesi = $qrToken->sesiAbsensi;
        $sesi = $this->finalizer->finalizeIfScheduleEnded($sesi);

        if ($sesi->status !== SesiAbsensi::STATUS_AKTIF || ! $sesi->tanggal?->isSameDay(CarbonImmutable::today())) {
            $this->invalidQr($request, $mahasiswa, 'Sesi absensi tidak aktif.', [
                'sesi_id' => $sesi->id,
                'status' => $sesi->status,
            ]);
        }

        $isParticipant = $sesi->jadwal?->kelas?->mahasiswa()
            ->whereKey($mahasiswa->id)
            ->exists();

        if (! $isParticipant) {
            $this->invalidQr($request, $mahasiswa, 'Anda tidak terdaftar pada kelas sesi ini.', [
                'sesi_id' => $sesi->id,
                'kelas_id' => $sesi->jadwal?->kelas_id,
            ]);
        }

        $alreadyPresent = Presensi::query()
            ->where('sesi_id', $sesi->id)
            ->where('mahasiswa_id', $mahasiswa->id)
            ->exists();

        if ($alreadyPresent) {
            $this->logFailedAttendance($request, $mahasiswa, 'qr_duplicate_attendance', [
                'sesi_id' => $sesi->id,
            ]);

            return response()->json([
                'message' => 'Presensi untuk sesi ini sudah tercatat.',
            ], 409);
        }

        $qrToken->increment('used_count');

        $request->session()->put('attendance_qr', [
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa->id,
            'verified_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
            'attempts' => 0,
        ]);
        $request->session()->forget('attendance_liveness');

        return response()->json([
            'message' => 'QR valid.',
            'next_url' => route('mahasiswa.absen.verifikasi-wajah.show'),
            'session' => $this->formatSession($sesi),
        ]);
    }

    public function faceVerification(Request $request): Response|RedirectResponse
    {
        $mahasiswa = $this->currentMahasiswa($request);
        $verification = $this->validQrVerification($request, $mahasiswa);

        if (! $verification) {
            return $this->expiredQrRedirect($request);
        }

        if (! $mahasiswa->faceData) {
            return redirect()
                ->route('mahasiswa.profil.show')
                ->with('error', 'Data wajah belum tersedia. Daftarkan wajah terlebih dahulu.');
        }

        $sesi = SesiAbsensi::query()
            ->with(['jadwal.kelas:id,nama_kelas,prodi', 'jadwal.dosen.user:id,name'])
            ->findOrFail($verification['sesi_id']);
        $sesi = $this->finalizer->finalizeIfScheduleEnded($sesi);

        if ($sesi->status !== SesiAbsensi::STATUS_AKTIF) {
            $request->session()->forget('attendance_qr');
            $request->session()->forget('attendance_liveness');

            return redirect()
                ->route('mahasiswa.absen.index')
                ->with('error', 'Sesi absensi sudah berakhir.');
        }

        $livenessChallenge = $this->createLivenessChallenge($request);

        return Inertia::render('Mahasiswa/Absen/VerifyFace', [
            'session' => $this->formatSession($sesi),
            'verificationExpiresAt' => $verification['expires_at'],
            'attemptsRemaining' => self::MAX_FACE_ATTEMPTS - (int) ($verification['attempts'] ?? 0),
            'faceConfig' => [
                'modelPath' => '/models',
                'threshold' => self::FACE_THRESHOLD,
                'descriptorLength' => self::FACE_DESCRIPTOR_LENGTH,
                'maxAttempts' => self::MAX_FACE_ATTEMPTS,
            ],
            'livenessChallenge' => $livenessChallenge,
        ]);
    }

    public function verifyFace(VerifyFaceRequest $request): JsonResponse
    {
        $mahasiswa = $this->currentMahasiswa($request);
        $verification = $this->validQrVerification($request, $mahasiswa);

        if (! $verification) {
            $this->logFailedAttendance($request, $mahasiswa, 'face_missing_or_expired_qr');

            return response()->json([
                'message' => 'Validasi QR sudah kedaluwarsa. Silakan scan ulang.',
                'next_url' => route('mahasiswa.absen.index'),
            ], 419);
        }

        $data = $request->validated();

        $sesi = SesiAbsensi::query()
            ->with(['jadwal.kelas:id,nama_kelas,prodi', 'jadwal.dosen.user:id,name'])
            ->findOrFail($verification['sesi_id']);
        $sesi = $this->finalizer->finalizeIfScheduleEnded($sesi);

        if ($sesi->status !== SesiAbsensi::STATUS_AKTIF || ! $sesi->tanggal?->isSameDay(CarbonImmutable::today())) {
            $request->session()->forget('attendance_qr');
            $request->session()->forget('attendance_liveness');
            $this->logFailedAttendance($request, $mahasiswa, 'face_session_inactive', [
                'sesi_id' => $sesi->id,
                'status' => $sesi->status,
            ]);

            return response()->json([
                'message' => 'Sesi absensi sudah tidak aktif.',
                'next_url' => route('mahasiswa.absen.gagal'),
            ], 409);
        }

        $alreadyPresent = Presensi::query()
            ->where('sesi_id', $sesi->id)
            ->where('mahasiswa_id', $mahasiswa->id)
            ->exists();

        if ($alreadyPresent) {
            $request->session()->forget('attendance_qr');
            $request->session()->forget('attendance_liveness');
            $this->logFailedAttendance($request, $mahasiswa, 'face_duplicate_attendance', [
                'sesi_id' => $sesi->id,
            ]);

            return response()->json([
                'message' => 'Presensi untuk sesi ini sudah tercatat.',
                'next_url' => route('mahasiswa.absen.index'),
            ], 409);
        }

        $registeredDescriptor = $mahasiswa->faceData?->face_descriptor;

        if (! $registeredDescriptor || count($registeredDescriptor) !== self::FACE_DESCRIPTOR_LENGTH) {
            $this->logFailedAttendance($request, $mahasiswa, 'face_registered_descriptor_invalid', [
                'sesi_id' => $sesi->id,
            ]);

            return response()->json([
                'message' => 'Data wajah tersimpan tidak lengkap.',
            ], 422);
        }

        if (! $this->hasValidLiveness($request, $data['liveness'] ?? null)) {
            $this->logFailedAttendance($request, $mahasiswa, 'face_liveness_failed', [
                'sesi_id' => $sesi->id,
            ]);

            return response()->json([
                'message' => 'Liveness detection belum valid. Ikuti instruksi buka mulut dan menoleh, lalu coba lagi.',
            ], 422);
        }

        $distance = $this->euclideanDistance(
            array_map('floatval', $registeredDescriptor),
            array_map('floatval', $data['face_descriptor']),
        );

        if ($distance > self::FACE_THRESHOLD) {
            $attempts = (int) ($verification['attempts'] ?? 0) + 1;
            $verification['attempts'] = $attempts;
            $request->session()->put('attendance_qr', $verification);
            $this->logFailedAttendance($request, $mahasiswa, 'face_descriptor_mismatch', [
                'sesi_id' => $sesi->id,
                'attempts' => $attempts,
                'distance' => round($distance, 4),
                'client_distance' => isset($data['client_distance']) ? round((float) $data['client_distance'], 4) : null,
            ]);

            if ($attempts >= self::MAX_FACE_ATTEMPTS) {
                $request->session()->forget('attendance_qr');
                $request->session()->forget('attendance_liveness');
                $request->session()->put('attendance_failure', [
                    'message' => 'Verifikasi wajah gagal setelah beberapa percobaan.',
                    'distance' => round($distance, 4),
                    'session' => $this->formatSession($sesi),
                ]);

                return response()->json([
                    'message' => 'Verifikasi wajah gagal.',
                    'distance' => round($distance, 4),
                    'attempts_remaining' => 0,
                    'next_url' => route('mahasiswa.absen.gagal'),
                ], 422);
            }

            return response()->json([
                'message' => 'Wajah tidak cocok. Coba lagi.',
                'distance' => round($distance, 4),
                'attempts_remaining' => self::MAX_FACE_ATTEMPTS - $attempts,
            ], 422);
        }

        $presensi = Presensi::query()->firstOrCreate(
            [
                'sesi_id' => $sesi->id,
                'mahasiswa_id' => $mahasiswa->id,
            ],
            [
                'status' => Presensi::STATUS_HADIR,
                'timestamp' => now(),
                'metode' => 'qr+face',
            ],
        );

        if ($presensi->wasRecentlyCreated) {
            event(new MahasiswaHadir($presensi));
        }

        $request->session()->forget('attendance_qr');
        $request->session()->forget('attendance_liveness');
        $request->session()->put('attendance_success', [
            'presensi_id' => $presensi->id,
            'distance' => round($distance, 4),
            'session' => $this->formatSession($sesi),
        ]);

        return response()->json([
            'message' => 'Presensi berhasil dicatat.',
            'distance' => round($distance, 4),
            'next_url' => route('mahasiswa.absen.sukses'),
        ]);
    }

    public function success(Request $request): Response|RedirectResponse
    {
        $result = $request->session()->get('attendance_success');

        if (! $result) {
            return redirect()->route('mahasiswa.absen.index');
        }

        return Inertia::render('Mahasiswa/Absen/Success', [
            'result' => $result,
        ]);
    }

    public function failed(Request $request): Response|RedirectResponse
    {
        $result = $request->session()->get('attendance_failure');

        if (! $result) {
            return redirect()->route('mahasiswa.absen.index');
        }

        return Inertia::render('Mahasiswa/Absen/Failed', [
            'result' => $result,
        ]);
    }

    private function currentMahasiswa(Request $request): Mahasiswa
    {
        $mahasiswa = $request->user()?->mahasiswa;

        abort_unless($mahasiswa, 403);

        return $mahasiswa;
    }

    private function validQrVerification(Request $request, Mahasiswa $mahasiswa): ?array
    {
        $verification = $request->session()->get('attendance_qr');

        if (
            ! $verification
            || (int) ($verification['mahasiswa_id'] ?? 0) !== $mahasiswa->id
            || CarbonImmutable::parse($verification['expires_at'])->isPast()
        ) {
            return null;
        }

        return $verification;
    }

    private function createLivenessChallenge(Request $request): array
    {
        $issuedAt = now();
        $challenge = [
            'id' => (string) Str::uuid(),
            'steps' => self::LIVENESS_STEPS,
            'issued_at' => $issuedAt->toIso8601String(),
            'expires_at' => $issuedAt->copy()->addMinutes(5)->toIso8601String(),
        ];

        $request->session()->put('attendance_liveness', $challenge);

        return $challenge;
    }

    private function hasValidLiveness(Request $request, mixed $payload): bool
    {
        $challenge = $request->session()->get('attendance_liveness');

        if (! is_array($challenge) || ! is_array($payload)) {
            return false;
        }

        if (! isset($payload['completed_at'], $challenge['issued_at'], $challenge['expires_at'])) {
            return false;
        }

        try {
            $completedAt = CarbonImmutable::parse($payload['completed_at']);
            $issuedAt = CarbonImmutable::parse($challenge['issued_at']);
            $expiresAt = CarbonImmutable::parse($challenge['expires_at']);
        } catch (\Throwable) {
            return false;
        }

        return ($payload['challenge_id'] ?? null) === ($challenge['id'] ?? null)
            && array_values($payload['steps'] ?? []) === array_values($challenge['steps'] ?? [])
            && $completedAt->greaterThanOrEqualTo($issuedAt)
            && $completedAt->greaterThanOrEqualTo(now()->subSeconds(30))
            && $completedAt->lessThanOrEqualTo(now()->addSeconds(10))
            && $expiresAt->isFuture();
    }

    private function expiredQrRedirect(Request $request): RedirectResponse
    {
        $request->session()->forget('attendance_qr');
        $request->session()->forget('attendance_liveness');

        return redirect()
            ->route('mahasiswa.absen.index')
            ->with('error', 'Validasi QR sudah kedaluwarsa. Silakan scan ulang.');
    }

    /**
     * @return array{sesi_id: int, token: string}
     */
    private function parseQrPayload(Request $request, Mahasiswa $mahasiswa, string $rawPayload): array
    {
        try {
            $payload = json_decode($rawPayload, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->invalidQr($request, $mahasiswa, 'Format QR tidak dikenali.');
        }

        if (
            ! is_array($payload)
            || ($payload['type'] ?? null) !== 'digital_attendance'
            || ! isset($payload['sesi_id'], $payload['token'])
        ) {
            $this->invalidQr($request, $mahasiswa, 'Format QR tidak sesuai.');
        }

        return [
            'sesi_id' => (int) $payload['sesi_id'],
            'token' => (string) $payload['token'],
        ];
    }

    private function invalidQr(Request $request, Mahasiswa $mahasiswa, string $message, array $context = []): never
    {
        $this->logFailedAttendance($request, $mahasiswa, 'qr_rejected', [
            'message' => $message,
            ...$context,
        ]);

        throw ValidationException::withMessages([
            'qr_payload' => $message,
        ]);
    }

    private function logFailedAttendance(Request $request, Mahasiswa $mahasiswa, string $reason, array $context = []): void
    {
        Log::warning('Percobaan presensi gagal.', [
            'reason' => $reason,
            'mahasiswa_id' => $mahasiswa->id,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            ...$context,
        ]);
    }

    /**
     * @param  array<int, float>  $left
     * @param  array<int, float>  $right
     */
    private function euclideanDistance(array $left, array $right): float
    {
        $sum = 0.0;

        for ($i = 0; $i < self::FACE_DESCRIPTOR_LENGTH; $i++) {
            $diff = ($left[$i] ?? 0.0) - ($right[$i] ?? 0.0);
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    private function formatSession(SesiAbsensi $sesi): array
    {
        return [
            'id' => $sesi->id,
            'mata_kuliah' => $sesi->jadwal?->mata_kuliah,
            'tanggal' => $sesi->tanggal?->format('Y-m-d'),
            'dibuka_at' => $sesi->dibuka_at?->format('H:i'),
            'hari' => $sesi->jadwal?->hari,
            'jam_mulai' => $this->formatTime($sesi->jadwal?->jam_mulai),
            'jam_selesai' => $this->formatTime($sesi->jadwal?->jam_selesai),
            'ruangan' => $sesi->jadwal?->ruangan,
            'dosen' => $sesi->jadwal?->dosen?->user?->name,
            'kelas' => $sesi->jadwal?->kelas ? [
                'id' => $sesi->jadwal->kelas->id,
                'nama_kelas' => $sesi->jadwal->kelas->nama_kelas,
                'prodi' => $sesi->jadwal->kelas->prodi,
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
