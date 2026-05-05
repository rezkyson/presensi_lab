<?php

namespace Database\Factories;

use App\Models\Ruangan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ruangan>
 */
class RuanganFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'LAB '.fake()->unique()->numberBetween(10, 99),
            'keterangan' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
