<?php

namespace App\Notifications;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudRechazadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Solicitud $solicitud;
    public string $motivo;

    /**
     * Create a new notification instance.
     */
    public function __construct(Solicitud $solicitud, string $motivo)
    {
        $this->solicitud = $solicitud;
        $this->motivo = $motivo;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $beneficiario = $notifiable;
        $nombreBeneficiario = $beneficiario->nombre ?? 'Beneficiario';
        
        return (new MailMessage)
            ->subject('Tu solicitud de ' . $this->solicitud->apoyo->nombre_apoyo . ' ha sido rechazada')
            ->greeting('Hola ' . $nombreBeneficiario)
            ->line('Lamentablemente, tu solicitud para el apoyo **' . $this->solicitud->apoyo->nombre_apoyo . '** ha sido **rechazada** por el área administrativa.')
            ->line('**Motivo del rechazo:**')
            ->line($this->motivo)
            ->line('Por favor, revisa los documentos requeridos y vuelve a presentar tu solicitud con los cambios solicitados.')
            ->line('Si tienes dudas, contacta al administrativo a través del sistema.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'solicitud_id' => $this->solicitud->folio,
            'apoyo' => $this->solicitud->apoyo->nombre_apoyo,
            'motivo' => $this->motivo,
            'tipo' => 'rechazo',
        ];
    }
}
