<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('apoyos.index', ['comentario_apoyo' => $apoyo->id_apoyo]) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Carga formal de documentos</h2>
                    <p class="text-xs text-slate-500">{{ $apoyo->nombre_apoyo }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 py-6 space-y-5">
        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm px-4 py-3">{{ session('error') }}</div>
        @endif

        @if(session('exito'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm px-4 py-3">Solicitud registrada correctamente.</div>
        @endif

        @if($solicitudActiva)
            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3">
                <p class="text-sm font-bold text-amber-800">Ya tienes una solicitud en proceso</p>
                <p class="text-xs text-amber-700 mt-1">Folio: {{ $solicitudActiva->folio }} · Estado: {{ $solicitudActiva->estado }}</p>
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
            <h3 class="text-base font-extrabold text-slate-800 mb-1">Documentos requeridos</h3>
            <p class="text-xs text-slate-500 mb-4">Adjunta los archivos solicitados para completar tu trámite.</p>

            <form id="formSolicitudFormal" action="{{ route('solicitud.guardar') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="apoyo" value="{{ $apoyo->id_apoyo }}">
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response-solicitud-formal">

                @if($requisitos->count())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($requisitos as $req)
                            <div class="rounded-xl border border-slate-200 p-3 bg-slate-50">
                                <label class="text-xs font-bold text-slate-700 block">{{ $req->nombre_documento }}</label>
                                <input class="mt-2 block w-full text-sm" type="file" name="documento_{{ $req->fk_id_tipo_doc }}"
                                    @if((int) $req->es_obligatorio === 1) required @endif>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500">Este apoyo no tiene documentos obligatorios configurados.</p>
                @endif

                <button type="submit" class="w-full rounded-lg bg-blue-700 text-white py-2.5 font-semibold disabled:opacity-50" @if($solicitudActiva) disabled @endif>
                    {{ $solicitudActiva ? 'Solicitud en proceso' : 'Enviar solicitud formal' }}
                </button>
            </form>
        </div>
    </div>

    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
    <script>
        const formSolicitudFormal = document.getElementById('formSolicitudFormal');
        if (formSolicitudFormal && !formSolicitudFormal.querySelector('button[type="submit"]').disabled) {
            formSolicitudFormal.addEventListener('submit', function (event) {
                if (typeof grecaptcha === 'undefined') {
                    return;
                }

                event.preventDefault();
                grecaptcha.ready(function () {
                    grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', { action: 'solicitud' })
                        .then(function (token) {
                            const tokenInput = document.getElementById('g-recaptcha-response-solicitud-formal');
                            if (tokenInput) {
                                tokenInput.value = token;
                            }
                            formSolicitudFormal.submit();
                        })
                        .catch(function () {
                            formSolicitudFormal.submit();
                        });
                });
            });
        }
    </script>
</x-app-layout>
