<?php

namespace Tests\Feature\Admin;

use App\Models\Jadwal;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RuanganManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_ruangan_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/ruangan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Ruangan/Index')
                ->has('ruangan.data', 3)
                ->has('filters')
            );
    }

    public function test_admin_can_create_ruangan(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/ruangan', [
                'nama' => 'LAB 4',
                'keterangan' => 'Lab komputer tambahan',
                'is_active' => true,
            ])
            ->assertRedirect('/admin/ruangan');

        $this->assertDatabaseHas('ruangan', [
            'nama' => 'LAB 4',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_ruangan(): void
    {
        $admin = User::factory()->admin()->create();
        $ruangan = Ruangan::factory()->create(['nama' => 'LAB 4']);
        $jadwal = Jadwal::factory()->create(['ruangan' => $ruangan->nama]);

        $this->actingAs($admin)
            ->put("/admin/ruangan/{$ruangan->id}", [
                'nama' => 'LAB 5',
                'keterangan' => 'Dipakai praktikum jaringan',
            ])
            ->assertRedirect('/admin/ruangan');

        $this->assertDatabaseHas('ruangan', [
            'id' => $ruangan->id,
            'nama' => 'LAB 5',
        ]);
        $this->assertDatabaseHas('jadwal', [
            'id' => $jadwal->id,
            'ruangan' => 'LAB 5',
        ]);
    }

    public function test_admin_can_toggle_ruangan_status(): void
    {
        $admin = User::factory()->admin()->create();
        $ruangan = Ruangan::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->patch("/admin/ruangan/{$ruangan->id}/toggle-active")
            ->assertRedirect();

        $this->assertDatabaseHas('ruangan', [
            'id' => $ruangan->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_delete_ruangan_used_by_jadwal(): void
    {
        $admin = User::factory()->admin()->create();
        $ruangan = Ruangan::query()->where('nama', 'LAB 1')->firstOrFail();
        Jadwal::factory()->create(['ruangan' => $ruangan->nama]);

        $this->actingAs($admin)
            ->from('/admin/ruangan')
            ->delete("/admin/ruangan/{$ruangan->id}")
            ->assertRedirect('/admin/ruangan')
            ->assertSessionHas('error', 'Ruangan tidak dapat dihapus karena masih digunakan jadwal.');

        $this->assertDatabaseHas('ruangan', ['id' => $ruangan->id]);
    }

    public function test_non_admin_cannot_access_ruangan_management(): void
    {
        $user = User::factory()->dosen()->create();

        $this->actingAs($user)
            ->get('/admin/ruangan')
            ->assertForbidden();
    }
}
