<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class AttendanceLoadTest extends TestCase
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

    public function test_thirty_students_can_attend_same_session_close_together(): void
    {
        $descriptor = array_fill(0, 128, 0.12);
        $kelas = Kelas::factory()->create();
        $dosen = Dosen::factory()->create();
        $jadwal = Jadwal::factory()->create([
            'kelas_id' => $kelas->id,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:40',
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
            'expired_at' => now()->addMinute(),
            'used_count' => 0,
        ]);

        $students = collect(range(1, 30))->map(function () use ($kelas, $descriptor) {
            $user = User::factory()->mahasiswa()->create();
            $mahasiswa = Mahasiswa::factory()->create([
                'user_id' => $user->id,
                'wajah_terdaftar' => true,
            ]);
            $kelas->mahasiswa()->attach($mahasiswa->id);
            FaceData::query()->create([
                'mahasiswa_id' => $mahasiswa->id,
                'foto_path' => 'face-data/test.jpg',
                'face_descriptor' => $descriptor,
            ]);

            return [$user, $mahasiswa];
        });

        foreach ($students as [$user]) {
            $this->actingAs($user)
                ->postJson('/mahasiswa/absen/verifikasi-qr', [
                    'qr_payload' => $this->payloadFor($token),
                ])
                ->assertOk();

            $this->withSession(['attendance_liveness' => $this->livenessChallenge()])
                ->actingAs($user)
                ->postJson('/mahasiswa/absen/verifikasi-wajah', [
                    'face_descriptor' => $descriptor,
                    'liveness' => $this->livenessPayload(),
                ])
                ->assertOk();
        }

        $this->assertDatabaseCount('presensi', 30);
        $this->assertSame(30, Presensi::query()->where('sesi_id', $sesi->id)->where('status', Presensi::STATUS_HADIR)->count());
        $this->assertDatabaseHas('qr_tokens', [
            'id' => $token->id,
            'used_count' => 30,
        ]);
    }

    private function payloadFor(QrToken $token): string
    {
        return json_encode([
            'type' => 'digital_attendance',
            'sesi_id' => $token->sesi_id,
            'token' => $token->token,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{id: string, steps: array<int, string>, issued_at: string, expires_at: string}
     */
    private function livenessChallenge(): array
    {
        return [
            'id' => 'load-test-liveness',
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
            'challenge_id' => 'load-test-liveness',
            'steps' => ['blink', 'turn_left', 'turn_right'],
            'completed_at' => now()->toIso8601String(),
        ];
    }
}
