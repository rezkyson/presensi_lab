<?php

namespace Tests\Feature;

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

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_summary_props(): void
    {
        $admin = User::factory()->admin()->create();
        Mahasiswa::factory()->count(2)->create();
        Dosen::factory()->create();
        $kelas = Kelas::factory()->create();
        $dosen = Dosen::factory()->create();
        Jadwal::factory()->create([
            'kelas_id' => $kelas->id,
            'dosen_id' => $dosen->id,
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.mahasiswa', 2)
                ->where('stats.dosen', 2)
                ->where('stats.kelas', 1)
                ->where('stats.jadwalAktif', 1)
                ->has('attendanceToday')
                ->has('recentSchedules', 1)
            );
    }

    public function test_dosen_dashboard_displays_today_and_active_session_props(): void
    {
        CarbonImmutable::setTestNow('2026-05-04 08:00:00');

        $user = User::factory()->dosen()->create();
        $dosen = Dosen::factory()->create(['user_id' => $user->id]);
        $kelas = Kelas::factory()->create();
        $jadwal = Jadwal::factory()->create([
            'kelas_id' => $kelas->id,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'mata_kuliah' => 'Pemrograman Web',
        ]);

        SesiAbsensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $dosen->id,
            'tanggal' => CarbonImmutable::today(),
            'status' => SesiAbsensi::STATUS_AKTIF,
        ]);

        $this->actingAs($user)
            ->get('/dosen/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dosen/Dashboard')
                ->where('todayName', 'Senin')
                ->has('todaySchedules', 1)
                ->has('activeSessions', 1)
            );

        CarbonImmutable::setTestNow();
    }

    public function test_mahasiswa_dashboard_displays_schedule_face_status_and_attendance(): void
    {
        CarbonImmutable::setTestNow('2026-05-04 08:00:00');

        $user = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'user_id' => $user->id,
            'wajah_terdaftar' => true,
        ]);
        $dosen = Dosen::factory()->create();
        $kelas = Kelas::factory()->create();
        $kelas->mahasiswa()->attach($mahasiswa->id);
        $jadwal = Jadwal::factory()->create([
            'kelas_id' => $kelas->id,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'mata_kuliah' => 'Basis Data',
        ]);
        $sesi = SesiAbsensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $dosen->id,
            'tanggal' => CarbonImmutable::today(),
            'status' => SesiAbsensi::STATUS_AKTIF,
        ]);
        Presensi::factory()->create([
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => Presensi::STATUS_HADIR,
        ]);

        $this->actingAs($user)
            ->get('/mahasiswa/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mahasiswa/Dashboard')
                ->where('todayName', 'Senin')
                ->where('faceRegistered', true)
                ->has('todaySchedules', 1)
                ->has('attendanceToday', 1)
            );

        CarbonImmutable::setTestNow();
    }
}
