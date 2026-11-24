<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('responsible-updates', function ($user) {
    return $user !== null;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Sinyal perubahan data SPJ (daftar & dashboard). Semua user login boleh mendengar;
// event tidak membawa data, jadi tidak ada yang bocor.
Broadcast::channel('spj-updates', function ($user) {
    return $user !== null;
});
