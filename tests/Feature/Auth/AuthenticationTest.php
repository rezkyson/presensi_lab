<?php

namespace Tests\Feature\Auth;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk();
    }

    public function test_admin_can_login_with_email_and_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'identifier' => 'admin@example.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/admin/dashboard');
    }

    public function test_dosen_can_login_with_email_and_is_redirected_to_dosen_dashboard(): void
    {
        $user = User::factory()->dosen()->create([
            'email' => 'dosen@example.test',
            'password' => Hash::make('password'),
        ]);

        Dosen::factory()->create(['user_id' => $user->id]);

        $response = $this->post('/login', [
            'identifier' => 'dosen@example.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dosen/dashboard');
    }

    public function test_mahasiswa_can_login_with_nim_and_is_redirected_to_mahasiswa_dashboard(): void
    {
        $user = User::factory()->mahasiswa()->create([
            'email' => 'mahasiswa@example.test',
            'password' => Hash::make('password'),
        ]);

        Mahasiswa::factory()->create([
            'user_id' => $user->id,
            'nim' => '2401010001',
        ]);

        $response = $this->post('/login', [
            'identifier' => '2401010001',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/mahasiswa/dashboard');
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->admin()->inactive()->create([
            'email' => 'inactive@example.test',
            'password' => Hash::make('password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'identifier' => 'inactive@example.test',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('identifier');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_protected_dashboard(): void
    {
        $this->get('/admin/dashboard')
            ->assertRedirect('/login');
    }

    public function test_role_middleware_blocks_wrong_role(): void
    {
        $user = User::factory()->mahasiswa()->create();

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_dashboard_redirects_authenticated_user_by_role(): void
    {
        $user = User::factory()->dosen()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/dosen/dashboard');
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->post('/login', [
                'identifier' => 'limited-login@example.test',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('identifier');
        }

        $this->post('/login', [
            'identifier' => 'limited-login@example.test',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }
}
