<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jadwal>
 */
class JadwalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slot = fake()->randomElement([
            ['08:00:00', '09:40:00'],
            ['10:00:00', '11:40:00'],
            ['13:00:00', '14:40:00'],
            ['15:00:00', '16:40:00'],
        ]);

        return [
            'kelas_id' => Kelas::factory(),
            'dosen_id' => Dosen::factory(),
            'mata_kuliah' => fake()->randomElement([
                'Pemrograman Web',
                'Basis Data',
                'Kecerdasan Buatan',
                'Jaringan Komputer',
            ]),
            'hari' => fake()->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']),
            'jam_mulai' => $slot[0],
            'jam_selesai' => $slot[1],
            'ruangan' => fake()->randomElement(['Lab A', 'Lab B', 'Lab C']),
        ];
    }
}
