<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\Presensi;
use App\Models\SesiAbsensi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Presensi>
 */
class PresensiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sesi_id' => SesiAbsensi::factory(),
            'mahasiswa_id' => Mahasiswa::factory(),
            'status' => Presensi::STATUS_HADIR,
            'timestamp' => now(),
            'metode' => 'qr+face',
        ];
    }
}
