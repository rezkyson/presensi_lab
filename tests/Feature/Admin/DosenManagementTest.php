<?php

namespace Tests\Feature\Admin;

use App\Models\Dosen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DosenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dosen_index(): void
    {
        $admin = User::factory()->admin()->create();
        Dosen::factory()->create([
            'nip' => '198801012020012001',
            'bidang_studi' => 'Basis Data',
        ]);

        $this->actingAs($admin)
            ->get('/admin/dosen')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dosen/Index')
                ->has('dosen.data', 1)
                ->has('filters')
            );
    }

    public function test_admin_can_create_dosen_and_related_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/dosen', [
                'name' => 'Dr. Nisa Rahma',
                'email' => 'nisa@example.test',
                'password' => 'secret123',
                'nip' => '198801012020012001',
                'bidang_studi' => 'Kecerdasan Buatan',
            ])
            ->assertRedirect('/admin/dosen');

        $this->assertDatabaseHas('users', [
            'email' => 'nisa@example.test',
            'role' => User::ROLE_DOSEN,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('dosen', [
            'nip' => '198801012020012001',
            'bidang_studi' => 'Kecerdasan Buatan',
        ]);
    }

    public function test_admin_cannot_create_dosen_with_duplicate_email_or_nip(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = Dosen::factory()->create(['nip' => '198801012020012001']);

        $this->actingAs($admin)
            ->from('/admin/dosen/create')
            ->post('/admin/dosen', [
                'name' => 'Duplikat',
                'email' => $existing->user->email,
                'nip' => '198801012020012001',
                'bidang_studi' => 'Basis Data',
            ])
            ->assertRedirect('/admin/dosen/create')
            ->assertSessionHasErrors(['email', 'nip']);
    }

    public function test_admin_can_update_dosen(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = Dosen::factory()->create(['nip' => '198801012020012001']);

        $this->actingAs($admin)
            ->put("/admin/dosen/{$dosen->id}", [
                'name' => 'Nama Dosen Baru',
                'email' => 'dosen.baru@example.test',
                'nip' => '198801012020012099',
                'bidang_studi' => 'Rekayasa Perangkat Lunak',
            ])
            ->assertRedirect('/admin/dosen');

        $dosen->refresh();
        $this->assertSame('Nama Dosen Baru', $dosen->user->name);
        $this->assertSame('dosen.baru@example.test', $dosen->user->email);
        $this->assertSame('198801012020012099', $dosen->nip);
        $this->assertSame('Rekayasa Perangkat Lunak', $dosen->bidang_studi);
    }

    public function test_admin_can_toggle_dosen_active_status(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = Dosen::factory()->create();

        $this->actingAs($admin)
            ->patch("/admin/dosen/{$dosen->id}/toggle-active")
            ->assertRedirect();

        $this->assertFalse($dosen->user->refresh()->is_active);
    }

    public function test_admin_can_reset_dosen_password(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = Dosen::factory()->create([
            'user_id' => User::factory()->dosen()->create([
                'password' => Hash::make('old-password'),
            ])->id,
        ]);

        $this->actingAs($admin)
            ->post("/admin/dosen/{$dosen->id}/reset-password")
            ->assertRedirect();

        $this->assertTrue(Hash::check('password', $dosen->user->refresh()->password));
    }

    public function test_admin_can_delete_dosen_and_related_user(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen = Dosen::factory()->create();
        $userId = $dosen->user_id;

        $this->actingAs($admin)
            ->delete("/admin/dosen/{$dosen->id}")
            ->assertRedirect('/admin/dosen');

        $this->assertDatabaseMissing('dosen', ['id' => $dosen->id]);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_non_admin_cannot_access_dosen_management(): void
    {
        $user = User::factory()->dosen()->create();

        $this->actingAs($user)
            ->get('/admin/dosen')
            ->assertForbidden();
    }
}
