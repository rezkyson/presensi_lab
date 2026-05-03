<?php

use App\Models\SesiAbsensi;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('sesi.{sesiId}', function (User $user, int $sesiId): bool {
    $sesi = SesiAbsensi::query()->find($sesiId);

    if (! $sesi) {
        return false;
    }

    if ($user->role === User::ROLE_ADMIN) {
        return true;
    }

    return $user->role === User::ROLE_DOSEN
        && $user->dosen?->id === $sesi->dosen_id;
});
