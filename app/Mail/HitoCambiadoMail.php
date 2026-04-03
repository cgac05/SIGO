<?php

namespace App\Mail;

use App\Models\Usuario;
use App\Models\HitosApoyo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HitoCambiadoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $beneficiario;
    public $hito;
    public $tipo_cambio;

    public function __construct(Usuario $beneficiario, HitosApoyo $hito, string $tipo_cambio)
    {
        $this->beneficiario = $beneficiario;
        $this->hito = $hito;
        $this->tipo_cambio = $tipo_cambio;
    }

    public function envelope(): Envelope
    {
        $nombreHito = $this->obtenerNombreHito($this->hito->tipo);
        
        return new Envelope(
            subject: "Progreso en tu Solicitud: {$nombreHito}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.hito-cambiado',
            with: [
                'beneficiario' => $this->beneficiario,
                'hito' => $this->hito,
                'tipo_cambio' => $this->tipo_cambio,
                'nombreHito' => $this->obtenerNombreHito($this->hito->tipo),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function obtenerNombreHito($tipo): string
    {
        $nombres = [
            1 => 'Publicación',
            2 => 'Recepción',
            3 => 'Análisis Administrativo',
            4 => 'Resultados',
            5 => 'Cierre',
        ];

        return $nombres[$tipo] ?? "Etapa {$tipo}";
    }
}
