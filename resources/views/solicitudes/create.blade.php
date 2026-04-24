<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Carga de Documentos - {{ config('app.name', 'SIGO') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        @php
            $modoReenvio = !empty($soloRechazados) && !empty($solicitudReenvio);
            $tituloFormulario = $modoReenvio ? 'Reenvío de documentos rechazados' : 'Carga formal de documentos';
            $textoFormulario = $modoReenvio
                ? 'Solo se muestran los documentos que fueron rechazados y deben volver a cargarse.'
                : 'Adjunta los archivos solicitados para completar tu trámite.';
        @endphp

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('apoyos.index', ['comentario_apoyo' => $apoyo->id_apoyo]) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <div>
                            <h2 class="text-xl font-extrabold text-slate-900">{{ $tituloFormulario }}</h2>
                            <p class="text-xs text-slate-500">{{ $apoyo->nombre_apoyo }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 py-6 space-y-5">
        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm px-4 py-3">{{ session('error') }}</div>
        @endif

        @if(session('exito'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm px-4 py-3">Solicitud registrada correctamente.</div>
        @endif

        @if($modoReenvio)
            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3">
                <p class="text-sm font-bold text-amber-800">Reenvío de documentos rechazados</p>
                <p class="text-xs text-amber-700 mt-1">Folio: {{ $solicitudReenvio->folio }} · Solo se mostrarán los documentos rechazados.</p>
            </div>
        @elseif($solicitudActiva)
            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3">
                <p class="text-sm font-bold text-amber-800">Ya tienes una solicitud en proceso</p>
                <p class="text-xs text-amber-700 mt-1">Folio: {{ $solicitudActiva->folio }} · Estado: {{ $solicitudActiva->estado }}</p>
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
            <h3 class="text-base font-extrabold text-slate-800 mb-1">{{ $modoReenvio ? 'Documentos rechazados' : 'Documentos requeridos' }}</h3>
            <p class="text-xs text-slate-500 mb-4">{{ $textoFormulario }}</p>

            <form id="formSolicitudFormal" action="{{ route('solicitud.guardar') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="apoyo" value="{{ $apoyo->id_apoyo }}">
                <input type="hidden" name="solo_rechazados" value="{{ $modoReenvio ? 1 : 0 }}">
                <input type="hidden" name="folio_rechazado" value="{{ $modoReenvio ? $solicitudReenvio->folio : '' }}">
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response-solicitud-formal">

                @if($requisitos->count())
                    <div class="space-y-4">
                        @foreach($requisitos as $req)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 overflow-hidden">
                                <div class="px-4 py-3 border-b border-slate-200">
                                    <label class="text-sm font-bold text-slate-800">{{ $req->nombre_documento }}</label>
                                    @if((int) $req->es_obligatorio !== 1)
                                        <span class="text-xs text-slate-500 ml-2">(Opcional)</span>
                                    @endif
                                    <p class="text-xs text-slate-600 mt-1">Formato: <span class="font-semibold">{{ strtoupper($req->tipo_archivo_permitido ?? 'PDF') }}</span></p>
                                </div>

                                <div class="px-4 py-4">
                                    <div class="flex gap-2 mb-4">
                                        <button type="button" class="flex-1 upload-source-btn rounded-lg border-2 border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-blue-500 hover:bg-blue-50 transition" data-source="local"
                                            onclick="switchUploadSource(this, {{ $req->fk_id_tipo_doc }})">
                                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.338-2.32 5.75 5.75 0 011.348 11.095H6.75z"/>
                                            </svg>
                                            Desde dispositivo
                                        </button>
                                        <button type="button" class="flex-1 upload-source-btn rounded-lg border-2 border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-blue-500 hover:bg-blue-50 transition" data-source="google_drive"
                                            onclick="switchUploadSource(this, {{ $req->fk_id_tipo_doc }})">
                                            <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M7 16.5l-3-5.196h6L7 16.5zm5-9l3-5.196 3 5.196h-6zm7 0l-3 5.196-3-5.196h6z"/>
                                            </svg>
                                            Google Drive
                                        </button>
                                    </div>

                                    <div class="upload-local-{{ $req->fk_id_tipo_doc }} upload-section">
                                        <div class="rounded-lg border-2 border-dashed border-slate-300 p-6 text-center hover:border-blue-400 hover:bg-blue-50 transition cursor-pointer" onclick="document.getElementById('file-{{ $req->fk_id_tipo_doc }}').click()">
                                            <svg class="w-8 h-8 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 16.17L4.83 12m0 0L12 4.83m-8.17 8.17L12 19.17m0 0l7.17-7.17m0 0L12 4.83"/>
                                            </svg>
                                            <p class="text-sm font-semibold text-slate-700">Haz clic o arrastra para cargar</p>
                                            <p class="text-xs text-slate-500 mt-1">Máximo 10 MB</p>
                                        </div>
                                        <input type="file" id="file-{{ $req->fk_id_tipo_doc }}" name="documento_{{ $req->fk_id_tipo_doc }}" class="hidden" data-doc-type="{{ $req->fk_id_tipo_doc }}" data-required="{{ $modoReenvio ? 1 : (int) $req->es_obligatorio }}" @if($modoReenvio || (int) $req->es_obligatorio === 1) required @endif onchange="updateFileDisplay(this)">
                                        <p class="text-xs text-slate-600 mt-2 file-name-{{ $req->fk_id_tipo_doc }}"></p>
                                    </div>

                                    <div class="upload-gdrive-{{ $req->fk_id_tipo_doc }} upload-section hidden">
                                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 mb-4">
                                            <p class="text-sm text-blue-800"><strong>Conectar con Google Drive</strong></p>
                                            <p class="text-xs text-blue-700 mt-1">Selecciona un archivo de tu Google Drive para vincularlo a esta solicitud.</p>
                                        </div>
                                        <button type="button" class="w-full rounded-lg bg-blue-100 border border-blue-300 text-blue-700 hover:bg-blue-200 font-semibold py-2.5 transition"
                                            onclick="launchGooglePicker({{ $req->fk_id_tipo_doc }})">
                                            <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>
                                            </svg>
                                            Seleccionar de Google Drive
                                        </button>
                                        <p class="text-xs text-slate-600 mt-2 gdrive-name-{{ $req->fk_id_tipo_doc }}"></p>
                                        <input type="hidden" name="gdrive_{{ $req->fk_id_tipo_doc }}_id" class="gdrive-id-{{ $req->fk_id_tipo_doc }}">
                                        <input type="hidden" name="gdrive_{{ $req->fk_id_tipo_doc }}_name" class="gdrive-name-input-{{ $req->fk_id_tipo_doc }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500">Este apoyo no tiene documentos obligatorios configurados.</p>
                @endif

                <button type="submit" class="w-full rounded-lg bg-blue-700 text-white py-3 font-bold text-base hover:bg-blue-600 disabled:opacity-50 transition shadow-md" @if($solicitudActiva && ! $modoReenvio) disabled @endif>
                    {{ $modoReenvio ? 'Reenviar documentos rechazados' : ($solicitudActiva ? 'Solicitud en proceso' : 'Enviar solicitud formal') }}
                </button>
            </form>
        </div>
    </div>

    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
    <script src="https://apis.google.com/js/api.js"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        const GOOGLE_CLIENT_ID = '{{ config('services.google.client_id') }}';
        const GOOGLE_API_KEY = '{{ config('services.google.api_key') }}';
        const GOOGLE_SCOPE = 'https://www.googleapis.com/auth/drive.readonly';
        
        let pickerTarget = null;
        let pickerApiReady = false;
        let accessToken = null;
        let tokenClient = null;

        function switchUploadSource(btn, docType) {
            const buttons = btn.parentElement.querySelectorAll('.upload-source-btn');
            buttons.forEach(b => {
                b.classList.remove('border-blue-500', 'bg-blue-50');
                b.classList.add('border-slate-300');
            });

            btn.classList.remove('border-slate-300');
            btn.classList.add('border-blue-500', 'bg-blue-50');

            const source = btn.dataset.source;
            const localDiv = document.querySelector(`.upload-local-${docType}`);
            const gdriveDiv = document.querySelector(`.upload-gdrive-${docType}`);

            if (source === 'local') {
                localDiv.classList.remove('hidden');
                gdriveDiv.classList.add('hidden');
            } else {
                localDiv.classList.add('hidden');
                gdriveDiv.classList.remove('hidden');
            }
        }

        function updateFileDisplay(input) {
            const docType = input.dataset.docType;
            const nameElement = document.querySelector(`.file-name-${docType}`);
            
            if (input.files && input.files[0]) {
                nameElement.textContent = `Archivo: ${input.files[0].name} (${(input.files[0].size / 1024 / 1024).toFixed(2)} MB)`;
                nameElement.classList.add('text-emerald-700', 'font-semibold');
            } else {
                nameElement.textContent = '';
                nameElement.classList.remove('text-emerald-700', 'font-semibold');
            }
        }

        function launchGooglePicker(docType) {
            if (!GOOGLE_CLIENT_ID || !GOOGLE_API_KEY) {
                alert('Falta configurar GOOGLE_CLIENT_ID o GOOGLE_API_KEY para usar Google Drive.');
                return;
            }

            pickerTarget = docType;

            if (!pickerApiReady) {
                gapi.load('picker', { callback: () => {
                    pickerApiReady = true;
                    requestGoogleAccessToken();
                }});
                return;
            }

            requestGoogleAccessToken();
        }

        function requestGoogleAccessToken() {
            if (!tokenClient || !window.google || !google.accounts || !google.accounts.oauth2) {
                alert('Google Identity Services no está listo. Intenta de nuevo en unos segundos.');
                return;
            }

            tokenClient.requestAccessToken({ prompt: accessToken ? '' : 'consent' });
        }

        function createPicker() {
            if (!accessToken) {
                alert('No se pudo obtener el token de Google para abrir Drive.');
                return;
            }

            const view = new google.picker.DocsView(google.picker.ViewId.DOCS)
                .setIncludeFolders(false)
                .setSelectFolderEnabled(false);

            const picker = new google.picker.PickerBuilder()
                .addView(view)
                .setDeveloperKey(GOOGLE_API_KEY)
                .setAppId(GOOGLE_CLIENT_ID.split('-')[0])
                .setOAuthToken(accessToken)
                .setCallback(pickerCallback)
                .setOrigin(window.location.origin)
                .build();

            picker.setVisible(true);
        }

        function pickerCallback(data) {
            if (data.action === google.picker.Action.PICKED) {
                const doc = data.docs[0];
                const docType = pickerTarget;

                document.querySelector(`.gdrive-id-${docType}`).value = doc.id;
                document.querySelector(`.gdrive-name-input-${docType}`).value = doc.name;
                document.querySelector(`.gdrive-name-${docType}`).textContent = `Archivo seleccionado: ${doc.name}`;
                document.querySelector(`.gdrive-name-${docType}`).classList.add('text-emerald-700', 'font-semibold');
            } else if (data.action === google.picker.Action.CANCEL) {
                console.log('Carga desde Google Drive cancelada');
            }
        }

        function initGoogleAPI() {
            if (window.google && google.accounts && google.accounts.oauth2 && GOOGLE_CLIENT_ID) {
                tokenClient = google.accounts.oauth2.initTokenClient({
                    client_id: GOOGLE_CLIENT_ID,
                    scope: GOOGLE_SCOPE,
                    callback: (resp) => {
                        if (resp && resp.access_token) {
                            accessToken = resp.access_token;
                            createPicker();
                            return;
                        }

                        alert('No fue posible autorizar el acceso a Google Drive.');
                    },
                });
            }
        }

        // Event listeners para recaptcha y validación de documentos
        const formSolicitudFormal = document.getElementById('formSolicitudFormal');
        if (formSolicitudFormal && !formSolicitudFormal.querySelector('button[type="submit"]').disabled) {
            formSolicitudFormal.addEventListener('submit', function (event) {
                if (typeof grecaptcha === 'undefined') {
                    return;
                }

                // Validar que todos los documentos obligatorios estén cargados
                const validationErrors = validateRequiredDocuments();
                if (validationErrors.length > 0) {
                    event.preventDefault();
                    showValidationErrors(validationErrors);
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

        /**
         * Validar que todos los documentos obligatorios estén cargados
         */
        function validateRequiredDocuments() {
            const errors = [];
            const fileInputs = formSolicitudFormal.querySelectorAll('input[type="file"][data-required="1"]');

            fileInputs.forEach(input => {
                const docType = input.dataset.docType;
                const localFile = input.files && input.files[0];
                const gdriveId = document.querySelector(`.gdrive-id-${docType}`);
                const gdriveValue = gdriveId ? gdriveId.value : '';

                // Validar que tenga al menos uno: archivo local O Google Drive
                if (!localFile && !gdriveValue) {
                    const docName = input.closest('.rounded-xl').querySelector('label').textContent;
                    errors.push(`${docName.trim()} es obligatorio`);
                }
            });

            return errors;
        }

        /**
         * Mostrar errores de validación
         */
        function showValidationErrors(errors) {
            // Crear contenedor de errores si no existe
            let errorContainer = document.getElementById('validation-errors');
            if (!errorContainer) {
                errorContainer = document.createElement('div');
                errorContainer.id = 'validation-errors';
                formSolicitudFormal.parentElement.insertBefore(errorContainer, formSolicitudFormal);
            }

            errorContainer.innerHTML = `
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 mb-4">
                    <p class="text-sm font-bold text-red-800 mb-2">Por favor completa los siguientes campos:</p>
                    <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                        ${errors.map(error => `<li>${error}</li>`).join('')}
                    </ul>
                </div>
            `;

            // Hacer scroll al error
            errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Inicializar Google API cuando la página carga
        document.addEventListener('DOMContentLoaded', function() {
            initGoogleAPI();
            
            // Seleccionar local como opción por defecto
            document.querySelectorAll('.upload-source-btn[data-source="local"]').forEach(btn => {
                btn.classList.remove('border-slate-300');
                btn.classList.add('border-blue-500', 'bg-blue-50');
            });

            window.setTimeout(initGoogleAPI, 1200);
        });
    </script>
        </main>
    </div>
</body>
</html>
