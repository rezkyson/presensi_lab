<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dosen>
 */
class DosenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->dosen(),
            'nip' => fake()->unique()->numerify('19##############'),
            'bidang_studi' => fake()->randomElement([
                'Rekayasa Perangkat Lunak',
                'Kecerdasan Buatan',
                'Jaringan Komputer',
                'Basis Data',
            ]),
        ];
    }
}
