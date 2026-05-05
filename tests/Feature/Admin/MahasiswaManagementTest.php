<?php

namespace Tests\Feature\Admin;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MahasiswaManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_mahasiswa_index(): void
    {
        $admin = User::factory()->admin()->create();
        Mahasiswa::factory()->create([
            'nim' => '2401010001',
            'prodi' => 'Teknik Informatika',
            'angkatan' => 2024,
        ]);

        $this->actingAs($admin)
            ->get('/admin/mahasiswa')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Mahasiswa/Index')
                ->has('mahasiswa.data', 1)
                ->has('filters')
                ->has('kelasOptions')
            );
    }

    public function test_admin_can_create_mahasiswa_and_related_user(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::create([
            'nama_kelas' => 'IF-1A',
            'prodi' => 'Teknik Informatika',
            'semester' => 2,
            'tahun_akademik' => '2025/2026',
        ]);

        $this->actingAs($admin)
            ->post('/admin/mahasiswa', [
                'name' => 'Ayu Lestari',
                'email' => 'ayu@example.test',
                'password' => 'secret123',
                'nim' => '2401010001',
                'prodi' => 'Teknik Informatika',
                'angkatan' => 2024,
                'kelas_ids' => [$kelas->id],
            ])
            ->assertRedirect('/admin/mahasiswa');

        $this->assertDatabaseHas('users', [
            'email' => 'ayu@example.test',
            'role' => User::ROLE_MAHASISWA,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('mahasiswa', [
            'nim' => '2401010001',
            'prodi' => 'Teknik Informatika',
            'wajah_terdaftar' => false,
        ]);

        $mahasiswa = Mahasiswa::where('nim', '2401010001')->firstOrFail();
        $this->assertTrue($mahasiswa->kelas()->whereKey($kelas->id)->exists());
    }

    public function test_admin_cannot_create_mahasiswa_with_duplicate_email_or_nim(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = Mahasiswa::factory()->create(['nim' => '2401010001']);

        $this->actingAs($admin)
            ->from('/admin/mahasiswa/create')
            ->post('/admin/mahasiswa', [
                'name' => 'Duplikat',
                'email' => $existing->user->email,
                'nim' => '2401010001',
                'prodi' => 'Teknik Informatika',
                'angkatan' => 2024,
                'kelas_ids' => [],
            ])
            ->assertRedirect('/admin/mahasiswa/create')
            ->assertSessionHasErrors(['email', 'nim']);
    }

    public function test_admin_can_update_mahasiswa_and_class_membership(): void
    {
        $admin = User::factory()->admin()->create();
        $mahasiswa = Mahasiswa::factory()->create(['nim' => '2401010001']);
        $kelas = Kelas::create([
            'nama_kelas' => 'SI-1A',
            'prodi' => 'Sistem Informasi',
            'semester' => 2,
            'tahun_akademik' => '2025/2026',
        ]);

        $this->actingAs($admin)
            ->put("/admin/mahasiswa/{$mahasiswa->id}", [
                'name' => 'Nama Baru',
                'email' => 'baru@example.test',
                'nim' => '2401010099',
                'prodi' => 'Sistem Informasi',
                'angkatan' => 2025,
                'kelas_ids' => [$kelas->id],
            ])
            ->assertRedirect('/admin/mahasiswa');

        $mahasiswa->refresh();
        $this->assertSame('Nama Baru', $mahasiswa->user->name);
        $this->assertSame('baru@example.test', $mahasiswa->user->email);
        $this->assertSame('2401010099', $mahasiswa->nim);
        $this->assertTrue($mahasiswa->kelas()->whereKey($kelas->id)->exists());
    }

    public function test_admin_can_toggle_mahasiswa_active_status(): void
    {
        $admin = User::factory()->admin()->create();
        $mahasiswa = Mahasiswa::factory()->create();

        $this->actingAs($admin)
            ->patch("/admin/mahasiswa/{$mahasiswa->id}/toggle-active")
            ->assertRedirect();

        $this->assertFalse($mahasiswa->user->refresh()->is_active);
    }

    public function test_admin_can_reset_mahasiswa_password(): void
    {
        $admin = User::factory()->admin()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'user_id' => User::factory()->mahasiswa()->create([
                'password' => Hash::make('old-password'),
            ])->id,
        ]);

        $this->actingAs($admin)
            ->post("/admin/mahasiswa/{$mahasiswa->id}/reset-password")
            ->assertRedirect();

        $this->assertTrue(Hash::check('password', $mahasiswa->user->refresh()->password));
    }

    public function test_admin_can_reset_mahasiswa_password_with_custom_value(): void
    {
        $admin = User::factory()->admin()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'user_id' => User::factory()->mahasiswa()->create([
                'password' => Hash::make('old-password'),
            ])->id,
        ]);

        $this->actingAs($admin)
            ->post("/admin/mahasiswa/{$mahasiswa->id}/reset-password", [
                'password' => 'mahasiswa123',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('mahasiswa123', $mahasiswa->user->refresh()->password));
    }

    public function test_admin_can_delete_mahasiswa_and_related_user(): void
    {
        $admin = User::factory()->admin()->create();
        $mahasiswa = Mahasiswa::factory()->create();
        $userId = $mahasiswa->user_id;

        $this->actingAs($admin)
            ->delete("/admin/mahasiswa/{$mahasiswa->id}")
            ->assertRedirect('/admin/mahasiswa');

        $this->assertDatabaseMissing('mahasiswa', ['id' => $mahasiswa->id]);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_non_admin_cannot_access_mahasiswa_management(): void
    {
        $user = User::factory()->mahasiswa()->create();

        $this->actingAs($user)
            ->get('/admin/mahasiswa')
            ->assertForbidden();
    }

}
