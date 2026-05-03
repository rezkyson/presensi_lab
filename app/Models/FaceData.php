<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceData extends Model
{
    protected $table = 'face_data';

    protected $fillable = [
        'mahasiswa_id',
        'foto_path',
        'face_descriptor',
    ];

    protected function casts(): array
    {
        return [
            'face_descriptor' => 'array',
        ];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
