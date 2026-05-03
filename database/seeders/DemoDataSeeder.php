<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dosenRplUser = User::factory()->dosen()->create([
            'name' => 'Dr. Rani Prasetya',
            'email' => 'rani@sihadir.local',
            'password' => Hash::make('password'),
        ]);

        $dosenAiUser = User::factory()->dosen()->create([
            'name' => 'Budi Santoso, M.Kom',
            'email' => 'budi@sihadir.local',
            'password' => Hash::make('password'),
        ]);

        $dosenRpl = Dosen::create([
            'user_id' => $dosenRplUser->id,
            'nip' => '198801012020012001',
            'bidang_studi' => 'Rekayasa Perangkat Lunak',
        ]);

        $dosenAi = Dosen::create([
            'user_id' => $dosenAiUser->id,
            'nip' => '198902022020012002',
            'bidang_studi' => 'Kecerdasan Buatan',
        ]);

        $kelasIf = Kelas::create([
            'nama_kelas' => 'IF-1A',
            'prodi' => 'Teknik Informatika',
            'semester' => 2,
            'tahun_akademik' => '2025/2026',
        ]);

        $kelasSi = Kelas::create([
            'nama_kelas' => 'SI-1A',
            'prodi' => 'Sistem Informasi',
            'semester' => 2,
            'tahun_akademik' => '2025/2026',
        ]);

        $mahasiswaIf = collect(range(1, 6))->map(function (int $index) {
            $user = User::factory()->mahasiswa()->create([
                'name' => sprintf('Mahasiswa IF %02d', $index),
                'email' => sprintf('mhs.if%02d@sihadir.local', $index),
                'password' => Hash::make('password'),
            ]);

            return Mahasiswa::create([
                'user_id' => $user->id,
                'nim' => sprintf('240101%04d', $index),
                'prodi' => 'Teknik Informatika',
                'angkatan' => 2024,
                'wajah_terdaftar' => false,
            ]);
        });

        $mahasiswaSi = collect(range(1, 4))->map(function (int $index) {
            $user = User::factory()->mahasiswa()->create([
                'name' => sprintf('Mahasiswa SI %02d', $index),
                'email' => sprintf('mhs.si%02d@sihadir.local', $index),
                'password' => Hash::make('password'),
            ]);

            return Mahasiswa::create([
                'user_id' => $user->id,
                'nim' => sprintf('240201%04d', $index),
                'prodi' => 'Sistem Informasi',
                'angkatan' => 2024,
                'wajah_terdaftar' => false,
            ]);
        });

        $kelasIf->mahasiswa()->sync($mahasiswaIf->pluck('id'));
        $kelasSi->mahasiswa()->sync($mahasiswaSi->pluck('id'));

        $kelasIf->dosen()->attach($dosenRpl->id, ['mata_kuliah' => 'Pemrograman Web']);
        $kelasIf->dosen()->attach($dosenAi->id, ['mata_kuliah' => 'Kecerdasan Buatan']);
        $kelasSi->dosen()->attach($dosenRpl->id, ['mata_kuliah' => 'Basis Data']);

        Jadwal::create([
            'kelas_id' => $kelasIf->id,
            'dosen_id' => $dosenRpl->id,
            'mata_kuliah' => 'Pemrograman Web',
            'hari' => 'Senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:40:00',
            'ruangan' => 'Lab A',
        ]);

        Jadwal::create([
            'kelas_id' => $kelasIf->id,
            'dosen_id' => $dosenAi->id,
            'mata_kuliah' => 'Kecerdasan Buatan',
            'hari' => 'Rabu',
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:40:00',
            'ruangan' => 'Lab B',
        ]);

        Jadwal::create([
            'kelas_id' => $kelasSi->id,
            'dosen_id' => $dosenRpl->id,
            'mata_kuliah' => 'Basis Data',
            'hari' => 'Jumat',
            'jam_mulai' => '13:00:00',
            'jam_selesai' => '14:40:00',
            'ruangan' => 'Lab C',
        ]);
    }
}
