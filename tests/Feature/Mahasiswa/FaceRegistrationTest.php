<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\FaceData;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FaceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_can_view_profile_page(): void
    {
        $user = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create([
            'user_id' => $user->id,
            'nim' => '2401010001',
            'prodi' => 'Teknik Informatika',
            'angkatan' => 2024,
        ]);

        $this->actingAs($user)
            ->get('/mahasiswa/profil')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mahasiswa/Profile')
                ->where('profile.nim', '2401010001')
                ->where('profile.wajah_terdaftar', false)
                ->where('faceConfig.descriptorLength', 128)
            );
    }

    public function test_mahasiswa_can_store_face_data(): void
    {
        Storage::fake('local');

        $user = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'user_id' => $user->id,
            'wajah_terdaftar' => false,
        ]);
        $descriptor = array_fill(0, 128, 0.123);

        $this->actingAs($user)
            ->from('/mahasiswa/profil')
            ->post('/mahasiswa/profil/wajah', [
                'image_base64' => $this->base64Png(),
                'face_descriptor' => $descriptor,
            ])
            ->assertRedirect('/mahasiswa/profil');

        $mahasiswa->refresh();
        $faceData = FaceData::where('mahasiswa_id', $mahasiswa->id)->firstOrFail();

        $this->assertTrue($mahasiswa->wajah_terdaftar);
        $this->assertCount(128, $faceData->face_descriptor);
        Storage::disk('local')->assertExists($faceData->foto_path);
    }

    public function test_store_face_data_validates_descriptor_length(): void
    {
        $user = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->from('/mahasiswa/profil')
            ->post('/mahasiswa/profil/wajah', [
                'image_base64' => $this->base64Png(),
                'face_descriptor' => array_fill(0, 127, 0.123),
            ])
            ->assertRedirect('/mahasiswa/profil')
            ->assertSessionHasErrors('face_descriptor');
    }

    public function test_mahasiswa_can_fetch_registered_face_descriptor(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'user_id' => $user->id,
            'wajah_terdaftar' => true,
        ]);
        FaceData::create([
            'mahasiswa_id' => $mahasiswa->id,
            'foto_path' => 'face-data/test.jpg',
            'face_descriptor' => array_fill(0, 128, 0.456),
        ]);

        $this->actingAs($user)
            ->getJson('/mahasiswa/face-descriptor')
            ->assertOk()
            ->assertJson([
                'registered' => true,
                'threshold' => 0.5,
            ])
            ->assertJsonCount(128, 'descriptor');
    }

    public function test_unregistered_descriptor_endpoint_returns_404(): void
    {
        $user = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/mahasiswa/face-descriptor')
            ->assertNotFound()
            ->assertJson([
                'registered' => false,
                'descriptor' => null,
            ]);
    }

    public function test_non_mahasiswa_cannot_access_profile_or_face_endpoints(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/mahasiswa/profil')
            ->assertForbidden();

        $this->actingAs($admin)
            ->getJson('/mahasiswa/face-descriptor')
            ->assertForbidden();
    }

    private function base64Png(): string
    {
        return 'data:image/png;base64,'.base64_encode(base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=',
            true,
        ));
    }
}
