<?php

namespace App\Mail;

use App\Models\Usuario;
use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudRechazadaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $beneficiario;
    public $solicitud;
    public $motivo;

    public function __construct(Usuario $beneficiario, Solicitud $solicitud, string $motivo)
    {
        $this->beneficiario = $beneficiario;
        $this->solicitud = $solicitud;
        $this->motivo = $motivo;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Solicitud Rechazada: {$this->solicitud->folio}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.solicitud-rechazada',
            with: [
                'beneficiario' => $this->beneficiario,
                'solicitud' => $this->solicitud,
                'motivo' => $this->motivo,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
