<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Ruangan;
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
        collect(['LAB 1', 'LAB 2', 'LAB 3'])->each(fn (string $nama) => Ruangan::firstOrCreate([
            'nama' => $nama,
        ], [
            'is_active' => true,
        ]));

        $dosenRplUser = User::factory()->dosen()->create([
            'name' => 'Dr. Rani Prasetya',
            'email' => 'rani.prasetya@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $dosenAiUser = User::factory()->dosen()->create([
            'name' => 'Budi Santoso, M.Kom',
            'email' => 'budi.santoso@gmail.com',
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

        $kelasTi = Kelas::create([
            'nama_kelas' => 'TI-2022',
            'prodi' => 'Teknik Informatika',
            'semester' => 6,
            'tahun_akademik' => '2025/2026',
        ]);

        $kelasSi = Kelas::create([
            'nama_kelas' => 'SI-2022',
            'prodi' => 'Sistem Informasi',
            'semester' => 6,
            'tahun_akademik' => '2025/2026',
        ]);

        $mahasiswaTi = collect([
            ['name' => 'Ahmad Fauzan', 'email' => 'ahmad.fauzan@gmail.com'],
            ['name' => 'Andini Putri Lestari', 'email' => 'andini.putri@gmail.com'],
            ['name' => 'Bagas Pratama', 'email' => 'bagas.pratama@gmail.com'],
            ['name' => 'Citra Ayu Wulandari', 'email' => 'citra.wulandari@gmail.com'],
            ['name' => 'Dimas Saputra', 'email' => 'dimas.saputra@gmail.com'],
            ['name' => 'Eka Rahmawati', 'email' => 'eka.rahmawati@gmail.com'],
        ])->map(function (array $student, int $index) {
            $user = User::factory()->mahasiswa()->create([
                'name' => $student['name'],
                'email' => $student['email'],
                'password' => Hash::make('password'),
            ]);

            return Mahasiswa::create([
                'user_id' => $user->id,
                'nim' => sprintf('220101%04d', $index + 1),
                'prodi' => 'Teknik Informatika',
                'angkatan' => 2022,
                'wajah_terdaftar' => false,
            ]);
        });

        $mahasiswaSi = collect([
            ['name' => 'Farhan Maulana', 'email' => 'farhan.maulana@gmail.com'],
            ['name' => 'Gita Permata Sari', 'email' => 'gita.permata@gmail.com'],
            ['name' => 'Hendra Wijaya', 'email' => 'hendra.wijaya@gmail.com'],
            ['name' => 'Indah Nuraini', 'email' => 'indah.nuraini@gmail.com'],
        ])->map(function (array $student, int $index) {
            $user = User::factory()->mahasiswa()->create([
                'name' => $student['name'],
                'email' => $student['email'],
                'password' => Hash::make('password'),
            ]);

            return Mahasiswa::create([
                'user_id' => $user->id,
                'nim' => sprintf('220201%04d', $index + 1),
                'prodi' => 'Sistem Informasi',
                'angkatan' => 2022,
                'wajah_terdaftar' => false,
            ]);
        });

        $kelasTi->mahasiswa()->sync($mahasiswaTi->pluck('id'));
        $kelasSi->mahasiswa()->sync($mahasiswaSi->pluck('id'));

        $kelasTi->dosen()->attach($dosenRpl->id, ['mata_kuliah' => 'Pemrograman Web']);
        $kelasTi->dosen()->attach($dosenAi->id, ['mata_kuliah' => 'Kecerdasan Buatan']);
        $kelasTi->dosen()->attach($dosenAi->id, ['mata_kuliah' => 'Jaringan Komputer']);
        $kelasSi->dosen()->attach($dosenRpl->id, ['mata_kuliah' => 'Basis Data']);
        $kelasSi->dosen()->attach($dosenRpl->id, ['mata_kuliah' => 'Analisis Sistem']);

        Jadwal::create([
            'kelas_id' => $kelasTi->id,
            'dosen_id' => $dosenRpl->id,
            'mata_kuliah' => 'Pemrograman Web',
            'hari' => 'Senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:40:00',
            'ruangan' => 'LAB 1',
        ]);

        Jadwal::create([
            'kelas_id' => $kelasSi->id,
            'dosen_id' => $dosenRpl->id,
            'mata_kuliah' => 'Basis Data',
            'hari' => 'Selasa',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:40:00',
            'ruangan' => 'LAB 2',
        ]);

        Jadwal::create([
            'kelas_id' => $kelasTi->id,
            'dosen_id' => $dosenAi->id,
            'mata_kuliah' => 'Kecerdasan Buatan',
            'hari' => 'Rabu',
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:40:00',
            'ruangan' => 'LAB 3',
        ]);

        Jadwal::create([
            'kelas_id' => $kelasSi->id,
            'dosen_id' => $dosenRpl->id,
            'mata_kuliah' => 'Analisis Sistem',
            'hari' => 'Kamis',
            'jam_mulai' => '13:00:00',
            'jam_selesai' => '14:40:00',
            'ruangan' => 'LAB 1',
        ]);

        Jadwal::create([
            'kelas_id' => $kelasTi->id,
            'dosen_id' => $dosenAi->id,
            'mata_kuliah' => 'Jaringan Komputer',
            'hari' => 'Jumat',
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:40:00',
            'ruangan' => 'LAB 2',
        ]);
    }
}
