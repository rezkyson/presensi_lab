<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mahasiswa>
 */
class MahasiswaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->mahasiswa(),
            'nim' => fake()->unique()->numerify('##########'),
            'prodi' => fake()->randomElement(['Teknik Informatika', 'Sistem Informasi']),
            'angkatan' => fake()->numberBetween(2022, 2026),
            'wajah_terdaftar' => false,
        ];
    }
}
