<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentoRechazadoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $beneficiario;
    public $nombreDocumento;
    public $motivo;
    public $idSolicitud;

    public function __construct(Usuario $beneficiario, string $nombreDocumento, string $motivo, ?int $idSolicitud = null)
    {
        $this->beneficiario = $beneficiario;
        $this->nombreDocumento = $nombreDocumento;
        $this->motivo = $motivo;
        $this->idSolicitud = $idSolicitud;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Documento Rechazado: {$this->nombreDocumento}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.documento-rechazado',
            with: [
                'beneficiario' => $this->beneficiario,
                'nombreDocumento' => $this->nombreDocumento,
                'motivo' => $this->motivo,
                'idSolicitud' => $this->idSolicitud,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
