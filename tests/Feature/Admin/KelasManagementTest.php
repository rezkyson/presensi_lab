<?php

namespace Tests\Feature\Admin;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KelasManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_kelas_index(): void
    {
        $admin = User::factory()->admin()->create();
        Kelas::factory()->create([
            'nama_kelas' => 'IF-1A',
            'prodi' => 'Teknik Informatika',
            'semester' => 2,
            'tahun_akademik' => '2025/2026',
        ]);

        $this->actingAs($admin)
            ->get('/admin/kelas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Kelas/Index')
                ->has('kelas.data', 1)
                ->has('filters')
                ->has('filterOptions')
            );
    }

    public function test_admin_can_create_kelas(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/kelas', [
                'nama_kelas' => 'IF-1A',
                'prodi' => 'Teknik Informatika',
                'semester' => 2,
                'tahun_akademik' => '2025/2026',
            ])
            ->assertRedirect('/admin/kelas');

        $this->assertDatabaseHas('kelas', [
            'nama_kelas' => 'IF-1A',
            'prodi' => 'Teknik Informatika',
            'semester' => 2,
            'tahun_akademik' => '2025/2026',
        ]);
    }

    public function test_admin_cannot_create_duplicate_kelas_identity(): void
    {
        $admin = User::factory()->admin()->create();
        Kelas::factory()->create([
            'nama_kelas' => 'IF-1A',
            'prodi' => 'Teknik Informatika',
            'semester' => 2,
            'tahun_akademik' => '2025/2026',
        ]);

        $this->actingAs($admin)
            ->from('/admin/kelas/create')
            ->post('/admin/kelas', [
                'nama_kelas' => 'IF-1A',
                'prodi' => 'Teknik Informatika',
                'semester' => 2,
                'tahun_akademik' => '2025/2026',
            ])
            ->assertRedirect('/admin/kelas/create')
            ->assertSessionHasErrors('nama_kelas');
    }

    public function test_admin_can_update_kelas(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();

        $this->actingAs($admin)
            ->put("/admin/kelas/{$kelas->id}", [
                'nama_kelas' => 'SI-2A',
                'prodi' => 'Sistem Informasi',
                'semester' => 4,
                'tahun_akademik' => '2026/2027',
            ])
            ->assertRedirect('/admin/kelas');

        $this->assertDatabaseHas('kelas', [
            'id' => $kelas->id,
            'nama_kelas' => 'SI-2A',
            'prodi' => 'Sistem Informasi',
            'semester' => 4,
            'tahun_akademik' => '2026/2027',
        ]);
    }

    public function test_admin_can_delete_kelas_without_jadwal(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();

        $this->actingAs($admin)
            ->delete("/admin/kelas/{$kelas->id}")
            ->assertRedirect('/admin/kelas');

        $this->assertDatabaseMissing('kelas', ['id' => $kelas->id]);
    }

    public function test_admin_cannot_delete_kelas_with_jadwal(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        Jadwal::factory()->create(['kelas_id' => $kelas->id]);

        $this->actingAs($admin)
            ->from('/admin/kelas')
            ->delete("/admin/kelas/{$kelas->id}")
            ->assertRedirect('/admin/kelas');

        $this->assertDatabaseHas('kelas', ['id' => $kelas->id]);
    }

    public function test_admin_can_view_kelas_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $mahasiswa = Mahasiswa::factory()->create();
        $dosen = Dosen::factory()->create();
        $kelas->mahasiswa()->attach($mahasiswa->id);
        $kelas->dosen()->attach($dosen->id, ['mata_kuliah' => 'Pemrograman Web']);

        $this->actingAs($admin)
            ->get("/admin/kelas/{$kelas->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Kelas/Show')
                ->where('kelas.id', $kelas->id)
                ->has('kelas.mahasiswa', 1)
                ->has('kelas.dosen', 1)
                ->has('mahasiswaOptions')
                ->has('dosenOptions')
            );
    }

    public function test_admin_can_attach_and_detach_mahasiswa(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $mahasiswa = Mahasiswa::factory()->create();

        $this->actingAs($admin)
            ->post("/admin/kelas/{$kelas->id}/mahasiswa", [
                'mahasiswa_id' => $mahasiswa->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kelas_mahasiswa', [
            'kelas_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
        ]);

        $this->actingAs($admin)
            ->delete("/admin/kelas/{$kelas->id}/mahasiswa/{$mahasiswa->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('kelas_mahasiswa', [
            'kelas_id' => $kelas->id,
            'mahasiswa_id' => $mahasiswa->id,
        ]);
    }

    public function test_admin_cannot_attach_duplicate_mahasiswa_to_same_kelas(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $mahasiswa = Mahasiswa::factory()->create();
        $kelas->mahasiswa()->attach($mahasiswa->id);

        $this->actingAs($admin)
            ->from("/admin/kelas/{$kelas->id}")
            ->post("/admin/kelas/{$kelas->id}/mahasiswa", [
                'mahasiswa_id' => $mahasiswa->id,
            ])
            ->assertRedirect("/admin/kelas/{$kelas->id}")
            ->assertSessionHasErrors('mahasiswa_id');
    }

    public function test_admin_can_attach_and_detach_dosen_pengampu(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $dosen = Dosen::factory()->create();

        $this->actingAs($admin)
            ->post("/admin/kelas/{$kelas->id}/dosen", [
                'dosen_id' => $dosen->id,
                'mata_kuliah' => 'Basis Data',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kelas_dosen', [
            'kelas_id' => $kelas->id,
            'dosen_id' => $dosen->id,
            'mata_kuliah' => 'Basis Data',
        ]);

        $pivotId = $kelas->dosen()->firstOrFail()->pivot->id;

        $this->actingAs($admin)
            ->delete("/admin/kelas/{$kelas->id}/dosen/{$pivotId}")
            ->assertRedirect();

        $this->assertDatabaseMissing('kelas_dosen', [
            'id' => $pivotId,
        ]);
    }

    public function test_admin_cannot_attach_duplicate_dosen_subject_to_same_kelas(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $dosen = Dosen::factory()->create();
        $kelas->dosen()->attach($dosen->id, ['mata_kuliah' => 'Basis Data']);

        $this->actingAs($admin)
            ->from("/admin/kelas/{$kelas->id}")
            ->post("/admin/kelas/{$kelas->id}/dosen", [
                'dosen_id' => $dosen->id,
                'mata_kuliah' => 'Basis Data',
            ])
            ->assertRedirect("/admin/kelas/{$kelas->id}")
            ->assertSessionHasErrors('mata_kuliah');
    }

    public function test_non_admin_cannot_access_kelas_management(): void
    {
        $user = User::factory()->dosen()->create();

        $this->actingAs($user)
            ->get('/admin/kelas')
            ->assertForbidden();
    }
}
