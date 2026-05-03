<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Dosen;
use App\Models\FaceData;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Presensi;
use App\Models\QrToken;
use App\Models\SesiAbsensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QrScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-05-04 08:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_mahasiswa_can_view_absen_page_with_active_sessions(): void
    {
        [$user] = $this->createActiveSessionForMahasiswa();

        $this->actingAs($user)
            ->get('/mahasiswa/absen')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mahasiswa/Absen/Index')
                ->where('faceRegistered', true)
                ->has('activeSessions', 1)
                ->has('attendanceToday', 0)
            );
    }

    public function test_mahasiswa_can_verify_valid_qr_token(): void
    {
        [$user, , $sesi, $token] = $this->createActiveSessionForMahasiswa();

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-qr', [
                'qr_payload' => $this->payloadFor($token),
            ])
            ->assertOk()
            ->assertJsonPath('message', 'QR valid.')
            ->assertJsonPath('next_url', url('/mahasiswa/absen/verifikasi-wajah'))
            ->assertSessionHas('attendance_qr.sesi_id', $sesi->id);

        $this->assertDatabaseHas('qr_tokens', [
            'id' => $token->id,
            'used_count' => 1,
        ]);
    }

    public function test_valid_qr_redirects_to_face_verification_page(): void
    {
        [$user, , , $token] = $this->createActiveSessionForMahasiswa();

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-qr', [
                'qr_payload' => $this->payloadFor($token),
            ])
            ->assertOk();

        $this->actingAs($user)
            ->get('/mahasiswa/absen/verifikasi-wajah')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mahasiswa/Absen/VerifyFace')
                ->has('session')
                ->has('verificationExpiresAt')
                ->where('faceConfig.threshold', 0.5)
            );
    }

    public function test_mahasiswa_without_registered_face_cannot_verify_qr(): void
    {
        [$user, , , $token] = $this->createActiveSessionForMahasiswa(faceRegistered: false);

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-qr', [
                'qr_payload' => $this->payloadFor($token),
            ])
            ->assertForbidden();
    }

    public function test_invalid_qr_payload_is_rejected(): void
    {
        [$user] = $this->createActiveSessionForMahasiswa();

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-qr', [
                'qr_payload' => 'not-json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qr_payload');
    }

    public function test_expired_qr_token_is_rejected(): void
    {
        [$user, , , $token] = $this->createActiveSessionForMahasiswa(tokenExpired: true);

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-qr', [
                'qr_payload' => $this->payloadFor($token),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qr_payload');
    }

    public function test_qr_token_is_bound_to_its_session(): void
    {
        [$user, , , $token] = $this->createActiveSessionForMahasiswa();

        $payload = json_encode([
            'type' => 'sihadir_attendance',
            'sesi_id' => $token->sesi_id + 999,
            'token' => $token->token,
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-qr', [
                'qr_payload' => $payload,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qr_payload');
    }

    public function test_closed_session_qr_is_rejected(): void
    {
        [$user, , , $token] = $this->createActiveSessionForMahasiswa(sessionStatus: SesiAbsensi::STATUS_SELESAI);

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-qr', [
                'qr_payload' => $this->payloadFor($token),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qr_payload');
    }

    public function test_mahasiswa_outside_class_cannot_verify_qr(): void
    {
        [$user, , , $token] = $this->createActiveSessionForMahasiswa(attachMahasiswa: false);

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-qr', [
                'qr_payload' => $this->payloadFor($token),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qr_payload');
    }

    public function test_mahasiswa_cannot_verify_qr_twice_after_attendance_exists(): void
    {
        [$user, $mahasiswa, $sesi, $token] = $this->createActiveSessionForMahasiswa();

        Presensi::factory()->create([
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => Presensi::STATUS_HADIR,
        ]);

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-qr', [
                'qr_payload' => $this->payloadFor($token),
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Presensi untuk sesi ini sudah tercatat.');
    }

    public function test_non_mahasiswa_cannot_access_absen_routes(): void
    {
        $admin = User::factory()->admin()->create();
        [, , , $token] = $this->createActiveSessionForMahasiswa();

        $this->actingAs($admin)
            ->get('/mahasiswa/absen')
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson('/mahasiswa/absen/verifikasi-qr', [
                'qr_payload' => $this->payloadFor($token),
            ])
            ->assertForbidden();
    }

    public function test_qr_verification_is_rate_limited(): void
    {
        [$user] = $this->createActiveSessionForMahasiswa();

        foreach (range(1, 30) as $attempt) {
            $this->actingAs($user)
                ->postJson('/mahasiswa/absen/verifikasi-qr', [
                    'qr_payload' => 'not-json',
                ])
                ->assertUnprocessable();
        }

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-qr', [
                'qr_payload' => 'not-json',
            ])
            ->assertTooManyRequests();
    }

    /**
     * @return array{0: User, 1: Mahasiswa, 2: SesiAbsensi, 3: QrToken, 4: Kelas}
     */
    private function createActiveSessionForMahasiswa(
        bool $faceRegistered = true,
        bool $attachMahasiswa = true,
        bool $tokenExpired = false,
        string $sessionStatus = SesiAbsensi::STATUS_AKTIF,
    ): array {
        $user = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'user_id' => $user->id,
            'wajah_terdaftar' => $faceRegistered,
        ]);

        if ($faceRegistered) {
            FaceData::query()->create([
                'mahasiswa_id' => $mahasiswa->id,
                'foto_path' => 'face-data/test.jpg',
                'face_descriptor' => array_fill(0, 128, 0.1),
            ]);
        }
        $kelas = Kelas::factory()->create();

        if ($attachMahasiswa) {
            $kelas->mahasiswa()->attach($mahasiswa->id);
        }

        $dosen = Dosen::factory()->create();
        $jadwal = Jadwal::factory()->create([
            'kelas_id' => $kelas->id,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:40',
            'ruangan' => 'Lab A',
            'mata_kuliah' => 'Pemrograman Web',
        ]);
        $sesi = SesiAbsensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $dosen->id,
            'tanggal' => CarbonImmutable::today(),
            'status' => $sessionStatus,
            'dibuka_at' => now(),
        ]);
        $token = QrToken::query()->create([
            'sesi_id' => $sesi->id,
            'token' => fake()->unique()->sha256(),
            'expired_at' => $tokenExpired ? now()->subSecond() : now()->addSeconds(60),
            'used_count' => 0,
        ]);

        return [$user, $mahasiswa, $sesi, $token, $kelas];
    }

    private function payloadFor(QrToken $token): string
    {
        return json_encode([
            'type' => 'sihadir_attendance',
            'sesi_id' => $token->sesi_id,
            'token' => $token->token,
        ], JSON_THROW_ON_ERROR);
    }
}
