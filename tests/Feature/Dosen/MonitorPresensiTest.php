<?php

namespace Tests\Feature\Dosen;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Presensi;
use App\Models\SesiAbsensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MonitorPresensiTest extends TestCase
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

    public function test_dosen_can_view_monitor_index(): void
    {
        [$user] = $this->createSessionWithParticipants();

        $this->actingAs($user)
            ->get('/dosen/monitor')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dosen/Monitor/Index')
                ->has('sessions', 1)
            );
    }

    public function test_dosen_can_view_session_monitor_with_participants_and_summary(): void
    {
        [$user, , $sesi] = $this->createSessionWithParticipants();

        $this->actingAs($user)
            ->get("/dosen/sesi/{$sesi->id}/monitor")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dosen/Monitor/Show')
                ->where('session.id', $sesi->id)
                ->where('attendance.summary.total', 4)
                ->where('attendance.summary.hadir', 1)
                ->where('attendance.summary.izin', 1)
                ->where('attendance.summary.sakit', 1)
                ->where('attendance.summary.belum_hadir', 1)
                ->has('attendance.participants', 4)
            );
    }

    public function test_dosen_can_fetch_attendance_endpoint(): void
    {
        [$user, , $sesi] = $this->createSessionWithParticipants();

        $this->actingAs($user)
            ->getJson("/dosen/sesi/{$sesi->id}/kehadiran")
            ->assertOk()
            ->assertJsonPath('attendance.summary.total', 4)
            ->assertJsonPath('attendance.summary.hadir', 1)
            ->assertJsonPath('attendance.summary.belum_hadir', 1)
            ->assertJsonCount(4, 'attendance.participants');
    }

    public function test_dosen_can_finalize_unattended_participant_after_session_closed(): void
    {
        [$user, , $sesi, , $mahasiswa] = $this->createSessionWithParticipants();
        $sesi->update([
            'status' => SesiAbsensi::STATUS_SELESAI,
            'ditutup_at' => now(),
        ]);

        $this->actingAs($user)
            ->patchJson("/dosen/sesi/{$sesi->id}/kehadiran/{$mahasiswa[3]->id}", [
                'status' => Presensi::STATUS_TIDAK_HADIR,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Status kehadiran berhasil diperbarui.')
            ->assertJsonPath('attendance.summary.tidak_hadir', 1)
            ->assertJsonPath('attendance.summary.belum_hadir', 0);

        $this->assertDatabaseHas('presensi', [
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa[3]->id,
            'status' => Presensi::STATUS_TIDAK_HADIR,
            'metode' => 'manual_dosen',
        ]);
    }

    public function test_dosen_cannot_finalize_unattended_participant_before_session_closed(): void
    {
        [$user, , $sesi, , $mahasiswa] = $this->createSessionWithParticipants();

        $this->actingAs($user)
            ->patchJson("/dosen/sesi/{$sesi->id}/kehadiran/{$mahasiswa[3]->id}", [
                'status' => Presensi::STATUS_IZIN,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Status belum absen bisa diverifikasi setelah sesi ditutup.');

        $this->assertDatabaseMissing('presensi', [
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa[3]->id,
        ]);
    }

    public function test_dosen_cannot_change_qr_face_attendance_from_monitor(): void
    {
        [$user, , $sesi, , $mahasiswa] = $this->createSessionWithParticipants();
        $sesi->update([
            'status' => SesiAbsensi::STATUS_SELESAI,
            'ditutup_at' => now(),
        ]);

        $this->actingAs($user)
            ->patchJson("/dosen/sesi/{$sesi->id}/kehadiran/{$mahasiswa[0]->id}", [
                'status' => Presensi::STATUS_SAKIT,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Mahasiswa yang sudah hadir lewat QR dan wajah tidak bisa diubah dari monitor.');

        $this->assertDatabaseHas('presensi', [
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa[0]->id,
            'status' => Presensi::STATUS_HADIR,
        ]);
    }

    public function test_dosen_cannot_monitor_other_dosen_session(): void
    {
        [$user] = $this->createSessionWithParticipants();
        [, , $otherSesi] = $this->createSessionWithParticipants();

        $this->actingAs($user)
            ->get("/dosen/sesi/{$otherSesi->id}/monitor")
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson("/dosen/sesi/{$otherSesi->id}/kehadiran")
            ->assertForbidden();
    }

    public function test_non_dosen_cannot_access_monitor_routes(): void
    {
        $admin = User::factory()->admin()->create();
        [, , $sesi] = $this->createSessionWithParticipants();

        $this->actingAs($admin)
            ->get('/dosen/monitor')
            ->assertForbidden();

        $this->actingAs($admin)
            ->get("/dosen/sesi/{$sesi->id}/monitor")
            ->assertForbidden();

        $this->actingAs($admin)
            ->getJson("/dosen/sesi/{$sesi->id}/kehadiran")
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Dosen, 2: SesiAbsensi, 3: Kelas, 4: \Illuminate\Database\Eloquent\Collection<int, Mahasiswa>}
     */
    private function createSessionWithParticipants(): array
    {
        $user = User::factory()->dosen()->create();
        $dosen = Dosen::factory()->create(['user_id' => $user->id]);
        $kelas = Kelas::factory()->create();
        $mahasiswa = Mahasiswa::factory()->count(4)->create();
        $kelas->mahasiswa()->attach($mahasiswa->pluck('id'));

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

        Presensi::factory()->create([
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa[0]->id,
            'status' => Presensi::STATUS_HADIR,
            'metode' => 'qr+face',
        ]);
        Presensi::factory()->create([
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa[1]->id,
            'status' => Presensi::STATUS_IZIN,
            'metode' => 'manual',
        ]);
        Presensi::factory()->create([
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa[2]->id,
            'status' => Presensi::STATUS_SAKIT,
            'metode' => 'manual',
        ]);

        return [$user, $dosen, $sesi, $kelas, $mahasiswa];
    }
}
