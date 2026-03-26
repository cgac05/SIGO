<?php

namespace App\Events;

use App\Models\Solicitud;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SolicitudRechazada
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Solicitud $solicitud;
    public string $motivo;

    /**
     * Create a new event instance.
     */
    public function __construct(Solicitud $solicitud, string $motivo)
    {
        $this->solicitud = $solicitud;
        $this->motivo = $motivo;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
