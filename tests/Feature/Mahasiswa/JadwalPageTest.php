<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class JadwalPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_can_view_their_schedule_page(): void
    {
        CarbonImmutable::setTestNow('2026-05-05 08:00:00');

        $user = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create(['user_id' => $user->id]);
        $kelas = Kelas::factory()->create([
            'nama_kelas' => 'TI-2022',
            'prodi' => 'Teknik Informatika',
        ]);
        $kelas->mahasiswa()->attach($mahasiswa->id);
        $dosen = Dosen::factory()->create();

        Jadwal::factory()->create([
            'kelas_id' => $kelas->id,
            'dosen_id' => $dosen->id,
            'hari' => 'Selasa',
            'mata_kuliah' => 'Basis Data',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:40',
            'ruangan' => 'Lab 2',
        ]);

        $this->actingAs($user)
            ->get('/mahasiswa/jadwal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mahasiswa/Jadwal/Index')
                ->where('todayName', 'Selasa')
                ->has('schedules', 1)
                ->where('schedules.0.mata_kuliah', 'Basis Data')
                ->where('schedules.0.is_today', true)
                ->where('schedules.0.schedule_status_label', 'Sedang berlangsung')
            );

        CarbonImmutable::setTestNow();
    }

    public function test_schedule_remains_ended_after_its_day_has_passed_in_the_same_week(): void
    {
        CarbonImmutable::setTestNow('2026-05-06 08:00:00');

        $user = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create(['user_id' => $user->id]);
        $kelas = Kelas::factory()->create();
        $kelas->mahasiswa()->attach($mahasiswa->id);

        Jadwal::factory()->create([
            'kelas_id' => $kelas->id,
            'hari' => 'Selasa',
            'mata_kuliah' => 'Basis Data',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:40',
        ]);

        $this->actingAs($user)
            ->get('/mahasiswa/jadwal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('todayName', 'Rabu')
                ->where('schedules.0.schedule_status_label', 'Telah berakhir')
                ->where('schedules.0.schedule_status_description', 'Jadwal minggu ini sudah berakhir.')
            );

        CarbonImmutable::setTestNow();
    }

    public function test_schedule_becomes_upcoming_again_in_the_next_week_cycle(): void
    {
        CarbonImmutable::setTestNow('2026-05-11 08:00:00');

        $user = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create(['user_id' => $user->id]);
        $kelas = Kelas::factory()->create();
        $kelas->mahasiswa()->attach($mahasiswa->id);

        Jadwal::factory()->create([
            'kelas_id' => $kelas->id,
            'hari' => 'Selasa',
            'mata_kuliah' => 'Basis Data',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:40',
        ]);

        $this->actingAs($user)
            ->get('/mahasiswa/jadwal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('todayName', 'Senin')
                ->where('schedules.0.schedule_status_label', 'Belum dimulai')
                ->where('schedules.0.schedule_status_description', 'Jadwal berlangsung pada hari Selasa.')
            );

        CarbonImmutable::setTestNow();
    }

    public function test_non_mahasiswa_cannot_access_schedule_page(): void
    {
        $dosen = User::factory()->dosen()->create();

        $this->actingAs($dosen)
            ->get('/mahasiswa/jadwal')
            ->assertForbidden();
    }
}
