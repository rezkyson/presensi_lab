<?php

namespace App\Events;

use App\Models\Presensi;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MahasiswaHadir implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Presensi $presensi)
    {
        $this->presensi->loadMissing('mahasiswa.user:id,name,email', 'sesiAbsensi:id');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sesi.'.$this->presensi->sesi_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'mahasiswa.hadir';
    }

    public function broadcastWith(): array
    {
        return [
            'presensi' => [
                'id' => $this->presensi->id,
                'sesi_id' => $this->presensi->sesi_id,
                'mahasiswa_id' => $this->presensi->mahasiswa_id,
                'nama' => $this->presensi->mahasiswa?->user?->name,
                'nim' => $this->presensi->mahasiswa?->nim,
                'status' => $this->presensi->status,
                'timestamp' => $this->presensi->timestamp?->format('H:i'),
                'metode' => $this->presensi->metode,
            ],
        ];
    }
}
