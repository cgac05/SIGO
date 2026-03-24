<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validacion Publica SIGO</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-100 to-white text-slate-900">
    <main class="max-w-2xl mx-auto px-4 py-10">
        <section class="bg-white rounded-2xl shadow-md border border-slate-200 p-6">
            <h1 class="text-2xl font-black text-slate-900">Portal Publico de Validacion</h1>
            <p class="text-sm text-slate-600 mt-1">Ingresa tu CUV para verificar estatus del apoyo.</p>

            <form method="GET" action="{{ route('solicitudes.publico.validar') }}" class="mt-6 flex gap-2">
                <input
                    type="text"
                    name="cuv"
                    value="{{ $cuv }}"
                    maxlength="20"
                    minlength="16"
                    class="flex-1 rounded-xl border-slate-300 text-sm uppercase"
                    placeholder="Ejemplo: 1A2B3C4D5E6F7G8H"
                    required
                >
                <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Validar</button>
            </form>

            @if($cuv !== '' && !$resultado)
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    No se encontro informacion para el CUV proporcionado.
                </div>
            @endif

            @if($resultado)
                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm space-y-2">
                    <div><span class="font-semibold">Apoyo:</span> {{ $resultado->nombre_apoyo }}</div>
                    <div><span class="font-semibold">Estatus:</span> {{ $resultado->fecha_cierre_financiero ? 'Entregado' : 'Autorizado' }}</div>
                    @if(!is_null($resultado->monto_entregado))
                        <div><span class="font-semibold">Monto otorgado:</span> {{ number_format((float) $resultado->monto_entregado, 2) }}</div>
                    @endif
                    <div><span class="font-semibold">Folio institucional:</span> {{ $resultado->folio_institucional ?: 'Pendiente' }}</div>
                </div>
            @endif
        </section>
    </main>
</body>
</html>
