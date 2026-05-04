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

class FaceAttendanceVerificationTest extends TestCase
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

    public function test_matching_face_descriptor_records_attendance(): void
    {
        [$user, $mahasiswa, $sesi] = $this->createVerifiedQrSession(array_fill(0, 128, 0.25));

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                'face_descriptor' => array_fill(0, 128, 0.25),
                'client_distance' => 0,
                'liveness' => $this->livenessPayload(),
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Presensi berhasil dicatat.')
            ->assertJsonPath('distance', 0)
            ->assertJsonPath('next_url', url('/mahasiswa/absen/sukses'))
            ->assertSessionMissing('attendance_qr')
            ->assertSessionHas('attendance_success.presensi_id');

        $this->assertDatabaseHas('presensi', [
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => Presensi::STATUS_HADIR,
            'metode' => 'qr+face',
        ]);
    }

    public function test_success_page_renders_after_face_verification(): void
    {
        [$user] = $this->createVerifiedQrSession(array_fill(0, 128, 0.2));

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                'face_descriptor' => array_fill(0, 128, 0.2),
                'liveness' => $this->livenessPayload(),
            ])
            ->assertOk();

        $this->actingAs($user)
            ->get('/mahasiswa/absen/sukses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mahasiswa/Absen/Success')
                ->has('result')
            );
    }

    public function test_face_verification_requires_valid_qr_session(): void
    {
        $user = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create([
            'user_id' => $user->id,
            'wajah_terdaftar' => true,
        ]);

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                'face_descriptor' => array_fill(0, 128, 0.2),
            ])
            ->assertStatus(419)
            ->assertJsonPath('next_url', url('/mahasiswa/absen'));
    }

    public function test_expired_qr_session_cannot_verify_face(): void
    {
        [$user, $mahasiswa, $sesi] = $this->createVerifiedQrSession(array_fill(0, 128, 0.2));

        $this->withSession([
            'attendance_qr' => [
                'sesi_id' => $sesi->id,
                'mahasiswa_id' => $mahasiswa->id,
                'verified_at' => now()->subMinutes(10)->toIso8601String(),
                'expires_at' => now()->subMinute()->toIso8601String(),
                'attempts' => 0,
            ],
        ])->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                'face_descriptor' => array_fill(0, 128, 0.2),
            ])
            ->assertStatus(419);
    }

    public function test_face_descriptor_length_is_validated(): void
    {
        [$user] = $this->createVerifiedQrSession(array_fill(0, 128, 0.2));

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                'face_descriptor' => array_fill(0, 127, 0.2),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('face_descriptor');
    }

    public function test_face_verification_requires_liveness_challenge(): void
    {
        [$user] = $this->createVerifiedQrSession(array_fill(0, 128, 0.2));

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                'face_descriptor' => array_fill(0, 128, 0.2),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Liveness detection belum valid. Ikuti instruksi kedip dan menoleh, lalu coba lagi.');
    }

    public function test_face_verification_rejects_stale_liveness_challenge(): void
    {
        [$user] = $this->createVerifiedQrSession(array_fill(0, 128, 0.2));
        $staleLiveness = $this->livenessPayload();

        CarbonImmutable::setTestNow('2026-05-04 08:01:00');

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                'face_descriptor' => array_fill(0, 128, 0.2),
                'liveness' => $staleLiveness,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Liveness detection belum valid. Ikuti instruksi kedip dan menoleh, lalu coba lagi.');
    }

    public function test_non_matching_face_descriptor_tracks_attempts_and_then_fails(): void
    {
        [$user] = $this->createVerifiedQrSession(array_fill(0, 128, 0.0));
        $descriptor = array_fill(0, 128, 0.2);

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                'face_descriptor' => $descriptor,
                'liveness' => $this->livenessPayload(),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('attempts_remaining', 2);

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                'face_descriptor' => $descriptor,
                'liveness' => $this->livenessPayload(),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('attempts_remaining', 1);

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                'face_descriptor' => $descriptor,
                'liveness' => $this->livenessPayload(),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('attempts_remaining', 0)
            ->assertJsonPath('next_url', url('/mahasiswa/absen/gagal'))
            ->assertSessionMissing('attendance_qr')
            ->assertSessionHas('attendance_failure');

        $this->assertDatabaseCount('presensi', 0);
    }

    public function test_failure_page_renders_after_max_attempts(): void
    {
        [$user] = $this->createVerifiedQrSession(array_fill(0, 128, 0.0));

        foreach (range(1, 3) as $attempt) {
            $this->actingAs($user)
                ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                    'face_descriptor' => array_fill(0, 128, 0.2),
                    'liveness' => $this->livenessPayload(),
                ]);
        }

        $this->actingAs($user)
            ->get('/mahasiswa/absen/gagal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mahasiswa/Absen/Failed')
                ->has('result')
            );
    }

    public function test_face_verification_prevents_duplicate_attendance(): void
    {
        [$user, $mahasiswa, $sesi] = $this->createVerifiedQrSession(array_fill(0, 128, 0.2));

        Presensi::factory()->create([
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => Presensi::STATUS_HADIR,
        ]);

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                'face_descriptor' => array_fill(0, 128, 0.2),
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Presensi untuk sesi ini sudah tercatat.');
    }

    public function test_face_verification_is_rate_limited(): void
    {
        $user = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create([
            'user_id' => $user->id,
            'wajah_terdaftar' => true,
        ]);

        foreach (range(1, 20) as $attempt) {
            $this->actingAs($user)
                ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                    'face_descriptor' => array_fill(0, 128, 0.2),
                ])
                ->assertStatus(419);
        }

        $this->actingAs($user)
            ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                'face_descriptor' => array_fill(0, 128, 0.2),
            ])
            ->assertTooManyRequests();
    }

    /**
     * @param  array<int, float>  $registeredDescriptor
     * @return array{0: User, 1: Mahasiswa, 2: SesiAbsensi, 3: QrToken}
     */
    private function createVerifiedQrSession(array $registeredDescriptor): array
    {
        $user = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'user_id' => $user->id,
            'wajah_terdaftar' => true,
        ]);
        FaceData::query()->create([
            'mahasiswa_id' => $mahasiswa->id,
            'foto_path' => 'face-data/test.jpg',
            'face_descriptor' => $registeredDescriptor,
        ]);

        $kelas = Kelas::factory()->create();
        $kelas->mahasiswa()->attach($mahasiswa->id);
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
            'status' => SesiAbsensi::STATUS_AKTIF,
            'dibuka_at' => now(),
        ]);
        $token = QrToken::query()->create([
            'sesi_id' => $sesi->id,
            'token' => fake()->unique()->sha256(),
            'expired_at' => now()->addSeconds(60),
            'used_count' => 1,
        ]);

        $this->withSession([
            'attendance_qr' => [
                'sesi_id' => $sesi->id,
                'mahasiswa_id' => $mahasiswa->id,
                'verified_at' => now()->toIso8601String(),
                'expires_at' => now()->addMinutes(5)->toIso8601String(),
                'attempts' => 0,
            ],
            'attendance_liveness' => $this->livenessChallenge(),
        ]);

        return [$user, $mahasiswa, $sesi, $token];
    }

    /**
     * @return array{id: string, steps: array<int, string>, issued_at: string, expires_at: string}
     */
    private function livenessChallenge(): array
    {
        return [
            'id' => 'test-liveness',
            'steps' => ['blink', 'turn_left', 'turn_right'],
            'issued_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
        ];
    }

    /**
     * @return array{challenge_id: string, steps: array<int, string>, completed_at: string}
     */
    private function livenessPayload(): array
    {
        return [
            'challenge_id' => 'test-liveness',
            'steps' => ['blink', 'turn_left', 'turn_right'],
            'completed_at' => now()->toIso8601String(),
        ];
    }
}
