<?php

namespace App\Models;

use Database\Factories\RuanganFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruangan extends Model
{
    /** @use HasFactory<RuanganFactory> */
    use HasFactory;

    protected $table = 'ruangan';

    protected $fillable = [
        'nama',
        'keterangan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
