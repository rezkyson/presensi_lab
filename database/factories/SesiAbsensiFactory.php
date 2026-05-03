<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\SesiAbsensi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SesiAbsensi>
 */
class SesiAbsensiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jadwal = Jadwal::factory()->create();

        return [
            'jadwal_id' => $jadwal->id,
            'dosen_id' => $jadwal->dosen_id,
            'tanggal' => today(),
            'status' => SesiAbsensi::STATUS_AKTIF,
            'dibuka_at' => now(),
            'ditutup_at' => null,
        ];
    }

    public function forDosen(Dosen $dosen): static
    {
        return $this->state(fn (array $attributes) => [
            'dosen_id' => $dosen->id,
        ]);
    }
}
