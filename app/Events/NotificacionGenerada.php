<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificacionGenerada implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $mensaje,
        public readonly string $evento,
        public readonly array $data = []
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('sigo.notificaciones.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'notificacion.generada';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'mensaje' => $this->mensaje,
            'evento' => $this->evento,
            'data' => $this->data,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
