<?php

namespace Tests\Feature\Admin;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\SesiAbsensi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class JadwalManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_jadwal_index(): void
    {
        $admin = User::factory()->admin()->create();
        Jadwal::factory()->create([
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:40',
        ]);

        $this->actingAs($admin)
            ->get('/admin/jadwal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Jadwal/Index')
                ->has('jadwal.data', 1)
                ->has('weeklySchedules')
                ->has('options')
            );
    }

    public function test_admin_can_create_jadwal(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $dosen = Dosen::factory()->create();

        $this->actingAs($admin)
            ->post('/admin/jadwal', [
                'kelas_id' => $kelas->id,
                'dosen_id' => $dosen->id,
                'mata_kuliah' => 'Pemrograman Web',
                'hari' => 'Senin',
                'jam_mulai' => '08:00',
                'jam_selesai' => '09:40',
                'ruangan' => 'Lab A',
            ])
            ->assertRedirect('/admin/jadwal');

        $this->assertDatabaseHas('jadwal', [
            'kelas_id' => $kelas->id,
            'dosen_id' => $dosen->id,
            'mata_kuliah' => 'Pemrograman Web',
            'hari' => 'Senin',
            'ruangan' => 'Lab A',
        ]);
    }

    public function test_admin_can_update_jadwal(): void
    {
        $admin = User::factory()->admin()->create();
        $jadwal = Jadwal::factory()->create([
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:40',
            'ruangan' => 'Lab A',
        ]);

        $this->actingAs($admin)
            ->put("/admin/jadwal/{$jadwal->id}", [
                'kelas_id' => $jadwal->kelas_id,
                'dosen_id' => $jadwal->dosen_id,
                'mata_kuliah' => 'Basis Data',
                'hari' => 'Selasa',
                'jam_mulai' => '10:00',
                'jam_selesai' => '11:40',
                'ruangan' => 'Lab B',
            ])
            ->assertRedirect('/admin/jadwal');

        $this->assertDatabaseHas('jadwal', [
            'id' => $jadwal->id,
            'mata_kuliah' => 'Basis Data',
            'hari' => 'Selasa',
            'ruangan' => 'Lab B',
        ]);
    }

    public function test_admin_cannot_create_jadwal_with_invalid_time_order(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $dosen = Dosen::factory()->create();

        $this->actingAs($admin)
            ->from('/admin/jadwal/create')
            ->post('/admin/jadwal', [
                'kelas_id' => $kelas->id,
                'dosen_id' => $dosen->id,
                'mata_kuliah' => 'Pemrograman Web',
                'hari' => 'Senin',
                'jam_mulai' => '10:00',
                'jam_selesai' => '09:40',
                'ruangan' => 'Lab A',
            ])
            ->assertRedirect('/admin/jadwal/create')
            ->assertSessionHasErrors('jam_selesai');
    }

    public function test_admin_cannot_create_overlapping_dosen_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = Dosen::factory()->create();
        Jadwal::factory()->create([
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'ruangan' => 'Lab A',
        ]);

        $this->actingAs($admin)
            ->from('/admin/jadwal/create')
            ->post('/admin/jadwal', [
                'kelas_id' => Kelas::factory()->create()->id,
                'dosen_id' => $dosen->id,
                'mata_kuliah' => 'Basis Data',
                'hari' => 'Senin',
                'jam_mulai' => '09:00',
                'jam_selesai' => '11:00',
                'ruangan' => 'Lab B',
            ])
            ->assertRedirect('/admin/jadwal/create')
            ->assertSessionHasErrors('dosen_id');
    }

    public function test_admin_cannot_create_overlapping_kelas_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        Jadwal::factory()->create([
            'kelas_id' => $kelas->id,
            'hari' => 'Rabu',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'ruangan' => 'Lab A',
        ]);

        $this->actingAs($admin)
            ->from('/admin/jadwal/create')
            ->post('/admin/jadwal', [
                'kelas_id' => $kelas->id,
                'dosen_id' => Dosen::factory()->create()->id,
                'mata_kuliah' => 'Basis Data',
                'hari' => 'Rabu',
                'jam_mulai' => '09:00',
                'jam_selesai' => '11:00',
                'ruangan' => 'Lab B',
            ])
            ->assertRedirect('/admin/jadwal/create')
            ->assertSessionHasErrors('kelas_id');
    }

    public function test_admin_cannot_create_overlapping_room_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        Jadwal::factory()->create([
            'hari' => 'Kamis',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'ruangan' => 'Lab A',
        ]);

        $this->actingAs($admin)
            ->from('/admin/jadwal/create')
            ->post('/admin/jadwal', [
                'kelas_id' => Kelas::factory()->create()->id,
                'dosen_id' => Dosen::factory()->create()->id,
                'mata_kuliah' => 'Basis Data',
                'hari' => 'Kamis',
                'jam_mulai' => '09:00',
                'jam_selesai' => '11:00',
                'ruangan' => 'Lab A',
            ])
            ->assertRedirect('/admin/jadwal/create')
            ->assertSessionHasErrors('ruangan');
    }

    public function test_admin_can_delete_jadwal_without_sesi(): void
    {
        $admin = User::factory()->admin()->create();
        $jadwal = Jadwal::factory()->create();

        $this->actingAs($admin)
            ->delete("/admin/jadwal/{$jadwal->id}")
            ->assertRedirect('/admin/jadwal');

        $this->assertDatabaseMissing('jadwal', ['id' => $jadwal->id]);
    }

    public function test_admin_cannot_delete_jadwal_with_sesi_absensi(): void
    {
        $admin = User::factory()->admin()->create();
        $jadwal = Jadwal::factory()->create();
        SesiAbsensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $jadwal->dosen_id,
        ]);

        $this->actingAs($admin)
            ->from('/admin/jadwal')
            ->delete("/admin/jadwal/{$jadwal->id}")
            ->assertRedirect('/admin/jadwal');

        $this->assertDatabaseHas('jadwal', ['id' => $jadwal->id]);
    }

    public function test_non_admin_cannot_access_jadwal_management(): void
    {
        $user = User::factory()->dosen()->create();

        $this->actingAs($user)
            ->get('/admin/jadwal')
            ->assertForbidden();
    }
}
