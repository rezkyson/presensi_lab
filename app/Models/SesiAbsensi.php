<?php

namespace App\Models;

use Database\Factories\SesiAbsensiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesiAbsensi extends Model
{
    public const STATUS_AKTIF = 'aktif';

    public const STATUS_SELESAI = 'selesai';

    /** @use HasFactory<SesiAbsensiFactory> */
    use HasFactory;

    protected $table = 'sesi_absensi';

    protected $fillable = [
        'jadwal_id',
        'dosen_id',
        'tanggal',
        'status',
        'dibuka_at',
        'ditutup_at',
    ];

    protected function casts(): array
    {
        return [
            'jadwal_id' => 'integer',
            'dosen_id' => 'integer',
            'tanggal' => 'date',
            'dibuka_at' => 'datetime',
            'ditutup_at' => 'datetime',
        ];
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    public function qrTokens(): HasMany
    {
        return $this->hasMany(QrToken::class, 'sesi_id');
    }

    public function presensi(): HasMany
    {
        return $this->hasMany(Presensi::class, 'sesi_id');
    }
}
