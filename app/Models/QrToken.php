<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrToken extends Model
{
    protected $fillable = [
        'sesi_id',
        'token',
        'expired_at',
        'used_count',
    ];

    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'used_count' => 'integer',
        ];
    }

    public function sesiAbsensi(): BelongsTo
    {
        return $this->belongsTo(SesiAbsensi::class, 'sesi_id');
    }
}
