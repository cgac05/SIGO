
@php
    $isBeneficiario = $user && $user->isBeneficiario();
    $isAdmin = $user && $user->personal && (int) $user->personal->fk_rol === 1;
    $isDirector = $user && $user->personal && (int) $user->personal->fk_rol === 2;
    $canEdit = $isAdmin || $isDirector;
    $userRole = $isBeneficiario ? 'beneficiario' : ($isDirector ? 'directivo' : ($isAdmin ? 'administrativo' : 'otro'));
@endphp

@vite('resources/js/apoyos-app.js')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Vista Unica de Apoyos</h2>
    </x-slot>

    <div class="min-h-screen bg-slate-50"
         x-data="apoyosApp(window.apoyosData, window.apoyosUserRole, window.apoyosCanEdit)">

        <script>
            window.apoyosData = @json($apoyos);
            window.apoyosUserRole = '{{ $userRole }}';
            window.apoyosCanEdit = {{ $canEdit ? 'true' : 'false' }};
            window.recaptchaSiteKey = '{{ config('services.recaptcha.site_key') }}';
        </script>

        <div class="mx-auto max-w-[1600px] px-4 py-6 md:px-6">
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 min-h-[calc(100vh-11rem)]">

                <section class="xl:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 md:p-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-5">
                        <div>
                            <h3 class="text-xl font-extrabold text-slate-800">Listado de Apoyos</h3>
                            @if($isBeneficiario)
                                <p class="text-sm text-slate-500">Puedes solicitar apoyos activos y subir tus documentos requeridos.</p>
                            @else
                                <p class="text-sm text-slate-500">Gestiona convocatorias, estatus y solicitudes del programa.</p>
                            @endif
                        </div>
                        @if($canEdit)
                            <a href="{{ route('apoyos.create') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold hover:bg-slate-700 transition">
                                + Nuevo apoyo
                            </a>
                        @endif
                    </div>

                    @if($isBeneficiario)
                        <div class="mb-5 bg-slate-100 rounded-xl border border-slate-200 p-4">
                            <h4 class="text-sm font-bold text-slate-700 mb-2">Mis solicitudes recientes</h4>
                            @if($misSolicitudes->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-slate-200 text-slate-500 text-left">
                                                <th class="py-2 pr-4">Folio</th>
                                                <th class="py-2 pr-4">Apoyo</th>
                                                <th class="py-2 pr-4">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($misSolicitudes as $solicitud)
                                                <tr class="border-b border-slate-100 text-slate-700">
                                                    <td class="py-2 pr-4 font-semibold">{{ $solicitud->folio }}</td>
                                                    <td class="py-2 pr-4">{{ $solicitud->nombre_apoyo ?? 'Sin nombre' }}</td>
                                                    <td class="py-2 pr-4">{{ $solicitud->estado ?? 'Pendiente' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-sm text-slate-500">Aun no registras solicitudes.</p>
                            @endif
                        </div>
                    @elseif($canEdit)
                        <div class="mb-5 bg-slate-100 rounded-xl border border-slate-200 p-4">
                            <h4 class="text-sm font-bold text-slate-700 mb-2">Solicitudes recibidas (ultimas 12)</h4>
                            @if($solicitudesRecientes->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-slate-200 text-slate-500 text-left">
                                                <th class="py-2 pr-4">Folio</th>
                                                <th class="py-2 pr-4">CURP</th>
                                                <th class="py-2 pr-4">Apoyo</th>
                                                <th class="py-2 pr-4">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($solicitudesRecientes as $solicitud)
                                                <tr class="border-b border-slate-100 text-slate-700">
                                                    <td class="py-2 pr-4 font-semibold">{{ $solicitud->folio }}</td>
                                                    <td class="py-2 pr-4">{{ $solicitud->fk_curp }}</td>
                                                    <td class="py-2 pr-4">{{ $solicitud->nombre_apoyo ?? 'Sin nombre' }}</td>
                                                    <td class="py-2 pr-4">{{ $solicitud->estado ?? 'Pendiente' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-sm text-slate-500">No hay solicitudes registradas todavia.</p>
                            @endif
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4">
                        <template x-for="apoyo in apoyos" :key="apoyo.id_apoyo">
                            <article class="rounded-xl border border-slate-200 overflow-hidden hover:shadow-md transition cursor-pointer" @click="abrirModal(apoyo)">
                                <template x-if="apoyo.foto_url">
                                    <img :src="apoyo.foto_url" :alt="apoyo.nombre_apoyo" class="w-full h-44 object-cover">
                                </template>
                                <template x-if="!apoyo.foto_url">
                                    <div class="h-44 bg-slate-200"></div>
                                </template>
                                <div class="p-4">
                                    <h4 class="font-bold text-slate-800 mb-1" x-text="apoyo.nombre_apoyo"></h4>
                                    <p class="text-xs text-slate-500 mb-3" x-text="apoyo.tipo_apoyo"></p>
                                    <button type="button" class="w-full rounded-lg bg-slate-900 text-white text-sm py-2 font-semibold">
                                        Ver detalle
                                    </button>
                                </div>
                            </article>
                        </template>
                    </div>

                    <template x-if="apoyos.length === 0">
                        <div class="rounded-xl border border-dashed border-slate-300 text-slate-500 text-center py-10 mt-4">
                            No hay apoyos disponibles por ahora.
                        </div>
                    </template>
                </section>

                <aside class="xl:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 md:p-6 flex flex-col">
                    <h3 class="text-lg font-extrabold text-slate-800 mb-1">Chat de comentarios</h3>
                    <p class="text-xs text-slate-500 mb-4">Mock visual: los mensajes solo viven en esta vista.</p>

                    <div class="flex-1 rounded-xl bg-slate-50 border border-slate-200 p-3 overflow-y-auto min-h-[320px] max-h-[60vh] space-y-3">
                        <template x-for="msg in chatMessages" :key="msg.id">
                            <div class="rounded-lg p-3" :class="msg.author === 'Tu' ? 'bg-slate-900 text-white ml-8' : 'bg-white border border-slate-200 mr-8'">
                                <div class="text-xs font-semibold" x-text="msg.author"></div>
                                <div class="text-sm" x-text="msg.body"></div>
                                <div class="text-[11px] opacity-70 mt-1" x-text="msg.time"></div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <input x-model="chatInput" @keydown.enter.prevent="enviarMensajeChat()" type="text" class="flex-1 rounded-lg border-slate-300 text-sm" placeholder="Escribe tu comentario...">
                        <button type="button" @click="enviarMensajeChat()" class="rounded-lg bg-blue-700 text-white px-4 text-sm font-semibold">Enviar</button>
                    </div>
                </aside>
            </div>
        </div>

        <div class="fixed inset-0 z-50 bg-black/60 p-4" x-show="modalAbierto" x-transition @click.self="cerrarModal()" style="display: none;">
            <div class="bg-white max-w-3xl w-full mx-auto rounded-2xl overflow-hidden max-h-[92vh] overflow-y-auto">
                <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-xl font-extrabold text-slate-800" x-text="apoyoActual && apoyoActual.nombre_apoyo"></h3>
                    <button type="button" class="text-slate-500" @click="cerrarModal()">Cerrar</button>
                </div>

                <div class="p-5 space-y-4">
                    <p class="text-sm text-slate-600" x-text="apoyoActual && apoyoActual.descripcion ? apoyoActual.descripcion : 'Sin descripcion disponible.'"></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg bg-slate-100 p-3">
                            <div class="text-slate-500 text-xs">Tipo</div>
                            <div class="font-semibold text-slate-800" x-text="apoyoActual && apoyoActual.tipo_apoyo"></div>
                        </div>
                        <div class="rounded-lg bg-slate-100 p-3">
                            <div class="text-slate-500 text-xs">Monto maximo</div>
                            <div class="font-semibold text-slate-800" x-text="'$' + Number(apoyoActual && apoyoActual.monto_maximo ? apoyoActual.monto_maximo : 0).toLocaleString('es-MX')"></div>
                        </div>
                    </div>

                    @if($isBeneficiario)
                        <form id="formSolicitud" action="{{ route('solicitud.guardar') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <input type="hidden" name="apoyo" :value="apoyoActual && apoyoActual.id_apoyo">
                            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response-solicitud">

                            <div class="rounded-lg bg-slate-100 border border-slate-200 p-4">
                                <p class="text-sm font-bold text-slate-700 mb-2">Subir Documentos</p>
                                <p class="text-xs text-slate-500 mb-3">Adjunta los archivos requeridos para solicitar este apoyo.</p>
                                <template x-if="apoyoActual && apoyoActual.requisitos && apoyoActual.requisitos.length > 0">
                                    <div class="space-y-2">
                                        <template x-for="req in apoyoActual.requisitos" :key="req.fk_id_tipo_doc">
                                            <div>
                                                <label class="text-xs font-semibold text-slate-600" x-text="req.nombre_documento"></label>
                                                <input class="mt-1 block w-full text-sm" type="file" :name="'documento_' + req.fk_id_tipo_doc" :accept="getAcceptByTipo(req)" :required="Number(req.es_obligatorio) === 1">
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!apoyoActual || !apoyoActual.requisitos || apoyoActual.requisitos.length === 0">
                                    <p class="text-xs text-slate-500">Este apoyo no tiene documentos obligatorios.</p>
                                </template>
                            </div>

                            <div>
                                <label for="mensaje-apoyo" class="text-sm font-bold text-slate-700">Comentario para la solicitud (opcional)</label>
                                <textarea id="mensaje-apoyo" x-model="mensajeUsuario" class="mt-1 w-full rounded-lg border-slate-300" rows="3" placeholder="Escribe un comentario breve..."></textarea>
                            </div>

                            <button type="button" @click="abrirConfirmacionSolicitud()" class="w-full rounded-lg bg-blue-700 text-white font-semibold py-2.5">
                                Solicitar apoyo
                            </button>
                        </form>
                    @elseif($canEdit)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <a :href="'/apoyos/' + (apoyoActual && apoyoActual.id_apoyo) + '/edit'" class="rounded-lg bg-slate-900 text-white py-2.5 text-center font-semibold">Editar apoyo</a>
                            <button type="button" @click="abrirEliminar(apoyoActual)" class="rounded-lg bg-red-600 text-white py-2.5 font-semibold">Eliminar apoyo</button>
                        </div>
                    @else
                        <button type="button" @click="cerrarModal()" class="w-full rounded-lg bg-slate-900 text-white py-2.5 font-semibold">Entendido</button>
                    @endif
                </div>
            </div>
        </div>

        <div class="fixed inset-0 z-[60] bg-black/60 p-4" x-show="confirmarEliminacionAbierto" x-transition style="display: none;">
            <div class="bg-white max-w-md mx-auto rounded-2xl p-6">
                <h4 class="text-lg font-extrabold text-slate-800 mb-2">Eliminar apoyo</h4>
                <p class="text-sm text-slate-500 mb-4">Esta accion no se puede deshacer.</p>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 py-2" @click="confirmarEliminacionAbierto = false">Cancelar</button>
                    <button type="button" class="rounded-lg bg-red-600 text-white py-2" @click="confirmarEliminacion()">Eliminar</button>
                </div>
            </div>
        </div>

        <div class="fixed inset-0 z-[60] bg-black/60 p-4" x-show="confirmarSolicitudAbierta" x-transition style="display: none;">
            <div class="bg-white max-w-md mx-auto rounded-2xl p-6">
                <h4 class="text-lg font-extrabold text-slate-800 mb-2">Confirmar solicitud</h4>
                <p class="text-sm text-slate-500 mb-4">Se enviara la solicitud con los archivos que adjuntaste.</p>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 py-2" @click="confirmarSolicitudAbierta = false">Cancelar</button>
                    <button type="button" class="rounded-lg bg-blue-700 text-white py-2" @click="enviarSolicitud()">Enviar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
</x-app-layout>
