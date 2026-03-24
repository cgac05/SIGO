<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('sigo.notificaciones.{userId}', function ($user, int $userId): bool {
    return (int) ($user->id_usuario ?? 0) === (int) $userId;
});
