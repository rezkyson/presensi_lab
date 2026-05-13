<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Presensi;
use App\Models\SesiAbsensi;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SesiAbsensiFinalizer
{
    public function finalize(SesiAbsensi $sesi, ?CarbonImmutable $closedAt = null): SesiAbsensi
    {
        return DB::transaction(function () use ($sesi, $closedAt) {
            $lockedSesi = SesiAbsensi::query()
                ->with('jadwal.kelas.mahasiswa:id')
                ->lockForUpdate()
                ->findOrFail($sesi->id);

            if ($lockedSesi->status === SesiAbsensi::STATUS_SELESAI) {
                return $lockedSesi;
            }

            $closedAt ??= CarbonImmutable::now();

            foreach ($lockedSesi->jadwal?->kelas?->mahasiswa ?? collect() as $mahasiswa) {
                Presensi::query()->firstOrCreate(
                    [
                        'sesi_id' => $lockedSesi->id,
                        'mahasiswa_id' => $mahasiswa->id,
                    ],
                    [
                        'status' => Presensi::STATUS_TIDAK_HADIR,
                        'timestamp' => $closedAt,
                        'metode' => 'auto_close',
                    ],
                );
            }

            $lockedSesi->qrTokens()->delete();
            $lockedSesi->update([
                'status' => SesiAbsensi::STATUS_SELESAI,
                'ditutup_at' => $closedAt,
            ]);

            return $lockedSesi->fresh(['jadwal.kelas']);
        });
    }

    public function finalizeIfScheduleEnded(SesiAbsensi $sesi): SesiAbsensi
    {
        $sesi->loadMissing('jadwal');

        if ($sesi->status !== SesiAbsensi::STATUS_AKTIF || ! $this->scheduleHasEnded($sesi)) {
            return $sesi;
        }

        return $this->finalize($sesi);
    }

    public function finalizeEndedForDosen(Dosen $dosen): void
    {
        $this->activeSessionsForDosen($dosen)
            ->each(fn (SesiAbsensi $sesi) => $this->finalizeIfScheduleEnded($sesi));
    }

    public function scheduleHasEnded(SesiAbsensi $sesi): bool
    {
        $end = $this->scheduledEndAt($sesi);

        return $end !== null && CarbonImmutable::now()->greaterThan($end);
    }

    /**
     * @return Collection<int, SesiAbsensi>
     */
    private function activeSessionsForDosen(Dosen $dosen): Collection
    {
        return SesiAbsensi::query()
            ->with('jadwal')
            ->where('dosen_id', $dosen->id)
            ->where('status', SesiAbsensi::STATUS_AKTIF)
            ->get();
    }

    private function scheduledEndAt(SesiAbsensi $sesi): ?CarbonImmutable
    {
        $date = $sesi->tanggal?->toDateString();
        $time = $this->formatTime($sesi->jadwal?->jam_selesai);

        if (! $date || ! $time) {
            return null;
        }

        return CarbonImmutable::parse($date)->setTimeFromTimeString($time);
    }

    private function formatTime(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        if (preg_match('/(\d{2}:\d{2})/', (string) $value, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
