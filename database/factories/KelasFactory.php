<?php

namespace Database\Factories;

use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_kelas' => fake()->unique()->bothify('??-#?'),
            'prodi' => fake()->randomElement(['Teknik Informatika', 'Sistem Informasi']),
            'semester' => fake()->numberBetween(1, 8),
            'tahun_akademik' => '2025/2026',
        ];
    }
}
