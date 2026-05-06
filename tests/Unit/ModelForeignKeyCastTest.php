<?php

namespace Tests\Unit;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\SesiAbsensi;
use PHPUnit\Framework\TestCase;

class ModelForeignKeyCastTest extends TestCase
{
    public function test_dosen_schedule_and_session_ids_are_cast_to_integers(): void
    {
        $dosen = new Dosen();
        $dosen->setRawAttributes(['id' => '7', 'user_id' => '3'], true);

        $jadwal = new Jadwal();
        $jadwal->setRawAttributes(['dosen_id' => '7', 'kelas_id' => '2'], true);

        $sesi = new SesiAbsensi();
        $sesi->setRawAttributes(['dosen_id' => '7', 'jadwal_id' => '4'], true);

        $this->assertSame(7, $dosen->id);
        $this->assertSame(7, $jadwal->dosen_id);
        $this->assertSame(7, $sesi->dosen_id);
        $this->assertSame($dosen->id, $jadwal->dosen_id);
        $this->assertSame($dosen->id, $sesi->dosen_id);
    }
}
