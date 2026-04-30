<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Entrega - Folio #{{ $solicitud->folio }}</title>
    <style>
        @page {
            margin: 40px 50px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #064e3b;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #064e3b;
            font-size: 20px;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            font-size: 11px;
            color: #666;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .info-table th, .info-table td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
        }
        .info-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: bold;
            width: 35%;
        }
        .section-title {
            background-color: #064e3b;
            color: #fff;
            padding: 6px 10px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .declaracion {
            border: 1px dashed #9ca3af;
            padding: 15px;
            margin-bottom: 50px;
            background-color: #f9fafb;
            text-align: justify;
        }
        .firma-box {
            width: 45%;
            display: inline-block;
            text-align: center;
            margin-top: 30px;
        }
        .firma-line {
            border-top: 1px solid #000;
            margin: 0 auto 5px auto;
            width: 80%;
        }
        .cuv-box {
            margin-top: 30px;
            text-align: center;
            font-family: monospace;
            font-size: 10px;
            color: #666;
        }
        .watermark {
            position: absolute;
            top: 40%;
            left: 20%;
            font-size: 80px;
            color: rgba(6, 78, 59, 0.05);
            transform: rotate(-45deg);
            z-index: -1;
            pointer-events: none;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="watermark">
        {{ $solicitud->tipo_apoyo === 'Económico' ? 'PAGADO' : 'ENTREGADO' }}
    </div>

    <div class="header">
        <div style="text-align: center; margin-bottom: 15px;">
            <img src="{{ public_path('images/logo injuve.png') }}" style="max-height: 80px; width: auto;" alt="Logo INJUVE">
        </div>
        <h1>
            @if($solicitud->tipo_apoyo === 'Económico')
                Acuse de Recibo de Pago Económico
            @else
                Comprobante de Salida y Entrega Físico
            @endif
        </h1>
        <p>Departamento de Recursos Financieros</p>
        <p>Fecha de emisión del reporte: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="section-title">Datos del Beneficiario</div>
    <table class="info-table">
        <tr>
            <th>Nombre Completo:</th>
            <td>{{ $solicitud->nombre }} {{ $solicitud->apellido_paterno }} {{ $solicitud->apellido_materno }}</td>
        </tr>
        <tr>
            <th>CURP:</th>
            <td>{{ $solicitud->curp }}</td>
        </tr>
        <tr>
            <th>Folio de la Solicitud:</th>
            <td><strong>#{{ $solicitud->folio }}</strong></td>
        </tr>
    </table>

    <div class="section-title">Detalles de la Entrega</div>
    <table class="info-table">
        <tr>
            <th>Nombre del Apoyo:</th>
            <td>{{ $solicitud->nombre_apoyo }}</td>
        </tr>
        <tr>
            <th>Tipo de Apoyo:</th>
            <td>{{ strtoupper($solicitud->tipo_apoyo) }}</td>
        </tr>
        <tr>
            <th>{{ $solicitud->tipo_apoyo === 'Económico' ? 'Monto Entregado:' : 'Valor de Salida del Apoyo:' }}</th>
            <td><strong>${{ number_format($solicitud->monto_entregado, 2) }} MXN</strong></td>
        </tr>
        <tr>
            <th>Fecha de Entrega / Pago:</th>
            <td>{{ \Carbon\Carbon::parse($solicitud->fecha_entrega_recurso)->format('d/m/Y') }}</td>
        </tr>
        @if($solicitud->tipo_apoyo === 'Económico')
        <tr>
            <th>Referencia / Folio Cheque:</th>
            <td>{{ $solicitud->folio_institucional ?? 'N/D' }}</td>
        </tr>
        @else
        <tr>
            <th>Estatus de Inventario:</th>
            <td>SALIDA REGISTRADA Y ENTREGADA</td>
        </tr>
        @endif
        <tr>
            <th>Director/a Autorizador:</th>
            <td>
                Aprobado el {{ \Carbon\Carbon::parse($solicitud->fecha_firma_directivo ?? $solicitud->fecha_creacion)->format('d/m/Y') }}<br>
                <span style="font-size: 9px; color:#555;">(CUV Directivo: {{ substr($solicitud->cuv, 0, 16) }}...)</span>
            </td>
        </tr>
    </table>

    <div class="declaracion">
        <p>
            @if($solicitud->tipo_apoyo === 'Económico')
                Por medio del presente, el área de Recursos Financieros certifica y hace constar el pago a favor del beneficiario titular de esta solicitud por el monto descrito, correspondientes a los rubros aprobados del apoyo.
            @else
                Hago constar mi recepción física, de total conformidad y en perfectas condiciones, de los insumos y materiales designados para la liberación y salida de inventario bajo el programa descrito.
            @endif
        </p>
    </div>

    <div style="text-align: center;">
        <div class="firma-box">
            <div class="firma-line"></div>
            <strong>Operador Financiero</strong><br>
            {{ $operadorNombre ?? 'Firma Autorizada' }}
        </div>
        <div class="firma-box">
            <div class="firma-line"></div>
            <strong>Recibí de Conformidad</strong><br>
            {{ $solicitud->nombre }} {{ $solicitud->apellido_paterno }}
        </div>
    </div>

    <div class="cuv-box">
        <p><strong>CÓDIGO ÚNICO DE VALIDACIÓN:</strong></p>
        <p>{{ $solicitud->cuv }}</p>
        @if($solicitud->sello_digital)
            <p><strong>SELLO:</strong> {{ substr($solicitud->sello_digital, 0, 50) }}...</p>
        @endif
        <p style="margin-top: 15px; font-size: 8px;">Este documento acredita el cierre financiero y recepción final de los recursos del programa. Generado automáticamente por SIGO.</p>
    </div>

</body>
</html>
