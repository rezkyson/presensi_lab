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

class RekapReportTest extends TestCase
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

    public function test_admin_can_filter_attendance_report(): void
    {
        $admin = User::factory()->admin()->create();
        [, , , $hadir] = $this->createAttendanceRecord(Presensi::STATUS_HADIR);
        $this->createAttendanceRecord(Presensi::STATUS_IZIN);

        $this->actingAs($admin)
            ->get('/admin/rekap?status=hadir')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Rekap/Index')
                ->where('stats.hadir', 1)
                ->where('stats.total', 1)
                ->where('records.data.0.id', $hadir->id)
            );
    }

    public function test_admin_can_export_pdf_and_excel(): void
    {
        $admin = User::factory()->admin()->create();
        $this->createAttendanceRecord(Presensi::STATUS_HADIR);

        $this->actingAs($admin)
            ->get('/admin/rekap/export/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->get('/admin/rekap/export/excel')
            ->assertOk()
            ->assertDownload('rekap-presensi-admin.xlsx');
    }

    public function test_dosen_can_view_owned_recap_and_export(): void
    {
        [, $dosen, $sesi] = $this->createAttendanceRecord(Presensi::STATUS_HADIR);
        $user = $dosen->user;

        $this->actingAs($user)
            ->get('/dosen/rekap')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dosen/Rekap/Index')
                ->where('sessions.data.0.id', $sesi->id)
                ->where('sessions.data.0.summary.hadir', 1)
            );

        $this->actingAs($user)
            ->get('/dosen/rekap/export/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get('/dosen/rekap/export/excel')
            ->assertOk()
            ->assertDownload('rekap-presensi-dosen.xlsx');
    }

    public function test_mahasiswa_can_view_personal_history_and_percentage(): void
    {
        [$mahasiswaUser, $mahasiswa, $dosen, $hadirSession] = $this->createStudentCourseHistory();

        Presensi::factory()->create([
            'sesi_id' => $hadirSession->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => Presensi::STATUS_HADIR,
            'metode' => 'qr+face',
        ]);

        $this->actingAs($mahasiswaUser)
            ->get('/mahasiswa/riwayat')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mahasiswa/Riwayat/Index')
                ->has('history.data', 1)
                ->where('history.data.0.dosen', $dosen->user?->name)
                ->where('summaries.0.mata_kuliah', 'Pemrograman Web')
                ->where('summaries.0.total_sesi', 2)
                ->where('summaries.0.persentase', 50)
            );
    }

    public function test_report_routes_are_role_protected(): void
    {
        $admin = User::factory()->admin()->create();
        $dosenUser = User::factory()->dosen()->create();
        Dosen::factory()->create(['user_id' => $dosenUser->id]);
        $mahasiswaUser = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $mahasiswaUser->id]);

        $this->actingAs($mahasiswaUser)->get('/admin/rekap')->assertForbidden();
        $this->actingAs($admin)->get('/dosen/rekap')->assertForbidden();
        $this->actingAs($dosenUser)->get('/mahasiswa/riwayat')->assertForbidden();
    }

    /**
     * @return array{0: Mahasiswa, 1: Dosen, 2: SesiAbsensi, 3: Presensi}
     */
    private function createAttendanceRecord(string $status): array
    {
        $kelas = Kelas::factory()->create();
        $mahasiswa = Mahasiswa::factory()->create();
        $kelas->mahasiswa()->attach($mahasiswa->id);
        $dosen = Dosen::factory()->create();
        $jadwal = Jadwal::factory()->create([
            'kelas_id' => $kelas->id,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'mata_kuliah' => 'Pemrograman Web',
        ]);
        $sesi = SesiAbsensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $dosen->id,
            'tanggal' => CarbonImmutable::today(),
            'status' => SesiAbsensi::STATUS_SELESAI,
        ]);
        $presensi = Presensi::factory()->create([
            'sesi_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => $status,
            'timestamp' => now(),
            'metode' => $status === Presensi::STATUS_HADIR ? 'qr+face' : 'manual',
        ]);

        return [$mahasiswa, $dosen, $sesi, $presensi];
    }

    /**
     * @return array{0: User, 1: Mahasiswa, 2: Dosen, 3: SesiAbsensi}
     */
    private function createStudentCourseHistory(): array
    {
        $mahasiswaUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create(['user_id' => $mahasiswaUser->id]);
        $kelas = Kelas::factory()->create();
        $kelas->mahasiswa()->attach($mahasiswa->id);
        $dosen = Dosen::factory()->create();
        $jadwal = Jadwal::factory()->create([
            'kelas_id' => $kelas->id,
            'dosen_id' => $dosen->id,
            'hari' => 'Senin',
            'mata_kuliah' => 'Pemrograman Web',
        ]);
        $hadirSession = SesiAbsensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $dosen->id,
            'tanggal' => CarbonImmutable::today(),
            'status' => SesiAbsensi::STATUS_SELESAI,
        ]);
        SesiAbsensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $dosen->id,
            'tanggal' => CarbonImmutable::today()->subWeek(),
            'status' => SesiAbsensi::STATUS_SELESAI,
        ]);

        return [$mahasiswaUser, $mahasiswa, $dosen, $hadirSession];
    }
}
