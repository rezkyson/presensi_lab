<?php

namespace Tests\Feature\Dosen;

use App\Models\Dosen;
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

class SesiAbsensiTest extends TestCase
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

    public function test_dosen_can_view_session_index_with_owned_schedules(): void
    {
        [$user, $dosen, $jadwal] = $this->createDosenSchedule();

        SesiAbsensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $dosen->id,
            'tanggal' => CarbonImmutable::today(),
            'status' => SesiAbsensi::STATUS_AKTIF,
        ]);

        $this->actingAs($user)
            ->get('/dosen/sesi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dosen/Sesi/Index')
                ->where('todayName', 'Senin')
                ->has('schedules', 1)
                ->has('activeSessions', 1)
            );
    }

    public function test_dosen_can_open_attendance_session_and_initial_qr_token(): void
    {
        [$user, $dosen, $jadwal] = $this->createDosenSchedule();

        $response = $this->actingAs($user)
            ->post("/dosen/jadwal/{$jadwal->id}/sesi");

        $sesi = SesiAbsensi::first();

        $response->assertRedirect("/dosen/sesi/{$sesi->id}/qr");

        $this->assertDatabaseHas('sesi_absensi', [
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $dosen->id,
            'tanggal' => CarbonImmutable::today()->toDateString(),
            'status' => SesiAbsensi::STATUS_AKTIF,
        ]);
        $this->assertDatabaseCount('qr_tokens', 1);
        $this->assertTrue(QrToken::first()->expired_at->greaterThan(now()));
    }

    public function test_opening_existing_active_session_reuses_session_and_rotates_token(): void
    {
        [$user, , $jadwal] = $this->createDosenSchedule();

        $this->actingAs($user)->post("/dosen/jadwal/{$jadwal->id}/sesi");
        $firstSessionId = SesiAbsensi::first()->id;
        $firstToken = QrToken::first()->token;

        $this->travel(5)->seconds();

        $this->actingAs($user)
            ->post("/dosen/jadwal/{$jadwal->id}/sesi")
            ->assertRedirect("/dosen/sesi/{$firstSessionId}/qr");

        $this->assertDatabaseCount('sesi_absensi', 1);
        $this->assertDatabaseCount('qr_tokens', 1);
        $this->assertNotSame($firstToken, QrToken::first()->token);
    }

    public function test_dosen_cannot_reopen_closed_session_for_same_schedule_date(): void
    {
        [$user, $dosen, $jadwal] = $this->createDosenSchedule();

        SesiAbsensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $dosen->id,
            'tanggal' => CarbonImmutable::today(),
            'status' => SesiAbsensi::STATUS_SELESAI,
            'ditutup_at' => now(),
        ]);

        $this->actingAs($user)
            ->from('/dosen/sesi')
            ->post("/dosen/jadwal/{$jadwal->id}/sesi")
            ->assertRedirect('/dosen/sesi')
            ->assertSessionHas('error', 'Sesi untuk jadwal ini hari ini sudah ditutup dan tidak bisa dibuka ulang.');

        $this->assertDatabaseCount('sesi_absensi', 1);
        $this->assertDatabaseCount('qr_tokens', 0);
    }

    public function test_closed_session_is_shown_as_finished_on_session_index(): void
    {
        [$user, $dosen, $jadwal] = $this->createDosenSchedule();
        $sesi = SesiAbsensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $dosen->id,
            'tanggal' => CarbonImmutable::today(),
            'status' => SesiAbsensi::STATUS_SELESAI,
            'ditutup_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dosen/sesi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dosen/Sesi/Index')
                ->where('schedules.0.active_session_id', null)
                ->where('schedules.0.completed_session_id', $sesi->id)
                ->where('schedules.0.can_open_session', false)
                ->where('schedules.0.unavailable_reason', 'Sesi hari ini sudah ditutup.')
            );
    }

    public function test_dosen_cannot_open_session_before_scheduled_day(): void
    {
        [$user, , $jadwal] = $this->createDosenSchedule();
        $jadwal->update(['hari' => 'Selasa']);

        $this->actingAs($user)
            ->from('/dosen/sesi')
            ->post("/dosen/jadwal/{$jadwal->id}/sesi")
            ->assertRedirect('/dosen/sesi')
            ->assertSessionHas('error', 'Sesi hanya bisa dibuka pada hari Selasa.');

        $this->assertDatabaseCount('sesi_absensi', 0);
        $this->assertDatabaseCount('qr_tokens', 0);
    }

    public function test_dosen_cannot_open_session_before_scheduled_time(): void
    {
        CarbonImmutable::setTestNow('2026-05-04 07:59:00');

        [$user, , $jadwal] = $this->createDosenSchedule();

        $this->actingAs($user)
            ->from('/dosen/sesi')
            ->post("/dosen/jadwal/{$jadwal->id}/sesi")
            ->assertRedirect('/dosen/sesi')
            ->assertSessionHas('error', 'Sesi belum dimulai. Jadwal dibuka pukul 08:00-09:40.');

        $this->assertDatabaseCount('sesi_absensi', 0);
        $this->assertDatabaseCount('qr_tokens', 0);
    }

    public function test_dosen_cannot_open_session_after_scheduled_time(): void
    {
        CarbonImmutable::setTestNow('2026-05-04 09:41:00');

        [$user, , $jadwal] = $this->createDosenSchedule();

        $this->actingAs($user)
            ->from('/dosen/sesi')
            ->post("/dosen/jadwal/{$jadwal->id}/sesi")
            ->assertRedirect('/dosen/sesi')
            ->assertSessionHas('error', 'Jadwal hari ini telah berakhir.');

        $this->assertDatabaseCount('sesi_absensi', 0);
        $this->assertDatabaseCount('qr_tokens', 0);
    }

    public function test_dosen_cannot_open_session_for_other_dosen_schedule(): void
    {
        [$user] = $this->createDosenSchedule();
        [, , $otherJadwal] = $this->createDosenSchedule();

        $this->actingAs($user)
            ->post("/dosen/jadwal/{$otherJadwal->id}/sesi")
            ->assertForbidden();
    }

    public function test_dosen_can_view_qr_page_for_owned_active_session(): void
    {
        [$user, $dosen, $jadwal] = $this->createDosenSchedule();
        $sesi = SesiAbsensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $dosen->id,
            'tanggal' => CarbonImmutable::today(),
            'status' => SesiAbsensi::STATUS_AKTIF,
        ]);

        $this->actingAs($user)
            ->get("/dosen/sesi/{$sesi->id}/qr")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dosen/Sesi/Qr')
                ->where('session.id', $sesi->id)
                ->where('qr.expires_in', 60)
                ->where('qr.data_uri', fn (string $value) => str_starts_with($value, 'data:image/png;base64,'))
            );
    }

    public function test_qr_data_refresh_invalidates_old_token(): void
    {
        [$user, $dosen, $jadwal] = $this->createDosenSchedule();
        $sesi = SesiAbsensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $dosen->id,
            'tanggal' => CarbonImmutable::today(),
            'status' => SesiAbsensi::STATUS_AKTIF,
        ]);
        $oldToken = QrToken::query()->create([
            'sesi_id' => $sesi->id,
            'token' => 'old-token',
            'expired_at' => now()->addSeconds(30),
        ]);

        $this->actingAs($user)
            ->getJson("/dosen/sesi/{$sesi->id}/qr-data")
            ->assertOk()
            ->assertJsonPath('qr.expires_in', 60)
            ->assertJsonPath('qr.data_uri', fn (string $value) => str_starts_with($value, 'data:image/png;base64,'));

        $this->assertDatabaseMissing('qr_tokens', ['id' => $oldToken->id]);
        $this->assertDatabaseCount('qr_tokens', 1);
        $this->assertNotSame('old-token', QrToken::first()->token);
    }

    public function test_dosen_can_close_session_and_mark_missing_students_absent(): void
    {
        [$user, $dosen, $jadwal, $kelas] = $this->createDosenSchedule();
        $present = Mahasiswa::factory()->create();
        $missing = Mahasiswa::factory()->create();
        $kelas->mahasiswa()->attach([$present->id, $missing->id]);

        $sesi = SesiAbsensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $dosen->id,
            'tanggal' => CarbonImmutable::today(),
            'status' => SesiAbsensi::STATUS_AKTIF,
        ]);
        QrToken::query()->create([
            'sesi_id' => $sesi->id,
            'token' => 'active-token',
            'expired_at' => now()->addSeconds(60),
        ]);
        Presensi::factory()->create([
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $present->id,
            'status' => Presensi::STATUS_HADIR,
            'metode' => 'qr+face',
        ]);

        $this->actingAs($user)
            ->delete("/dosen/sesi/{$sesi->id}")
            ->assertRedirect('/dosen/sesi');

        $this->assertDatabaseHas('sesi_absensi', [
            'id' => $sesi->id,
            'status' => SesiAbsensi::STATUS_SELESAI,
        ]);
        $this->assertDatabaseHas('presensi', [
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $present->id,
            'status' => Presensi::STATUS_HADIR,
        ]);
        $this->assertDatabaseHas('presensi', [
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $missing->id,
            'status' => Presensi::STATUS_TIDAK_HADIR,
            'metode' => 'auto_close',
        ]);
        $this->assertDatabaseCount('qr_tokens', 0);
    }

    public function test_non_dosen_cannot_access_dosen_sessions(): void
    {
        $admin = User::factory()->admin()->create();
        [, , $jadwal] = $this->createDosenSchedule();

        $this->actingAs($admin)
            ->get('/dosen/sesi')
            ->assertForbidden();

        $this->actingAs($admin)
            ->post("/dosen/jadwal/{$jadwal->id}/sesi")
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Dosen, 2: Jadwal, 3: Kelas}
     */
    private function createDosenSchedule(): array
    {
        $user = User::factory()->dosen()->create();
        $dosen = Dosen::factory()->create(['user_id' => $user->id]);
        $kelas = Kelas::factory()->create();
        $jadwal = Jadwal::factory()->create([
            'kelas_id' => $kelas->id,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:40',
            'ruangan' => 'Lab A',
            'mata_kuliah' => 'Pemrograman Web',
        ]);

        return [$user, $dosen, $jadwal, $kelas];
    }
}
