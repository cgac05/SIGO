@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-green-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                <svg class="w-8 h-8 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7-4a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Momento 2: Escaneo Asincrónico de Documentos
            </h1>
            <p class="text-gray-600 mt-2">Admin: Cargar documentos físicos escaneados después</p>
        </div>
        <!-- Info Alert -->
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-600 rounded-lg">
            <p class="text-green-800 font-semibold">✨ Arquitectura Fusionada: </p>
            <p class="text-green-700 text-sm mt-1">
                Después de escanear aquí, la solicitud entra al <strong>verificador ordinario</strong> (no hay interfaz separada).
                El admin verifica documentos usando la misma interfaz que para beneficiarios normales.
            </p>
        </div>
        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="text-sm text-gray-600">Pendientes de Escaneo</p>
                <p id="contadorPendientes" class="text-2xl font-bold text-yellow-600">{{ $estadisticas['pendientes'] ?? 0 }}</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-gray-600">Documentos Completados</p>
                <p class="text-2xl font-bold text-green-600">{{ $estadisticas['completados'] ?? 0 }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm text-gray-600">Con Error</p>
                <p class="text-2xl font-bold text-red-600">{{ $estadisticas['conError'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Tabla de Pendientes (Selección Rápida) -->
        <div id="tablaPendientesContainer" class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Folios Pendientes de Escaneo
                </h2>
                <div class="flex items-center gap-3">
                    <span id="badgePendientes" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        0 pendientes
                    </span>
                    <button onclick="cargarPendientes()" title="Recargar lista de pendientes" 
                            class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 hover:bg-yellow-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
            </div>
            <div class="overflow-x-auto">
                <table id="tablaPendientes" class="w-full text-sm">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="text-left px-4 py-3">Folio</th>
                            <th class="text-left px-4 py-3">Beneficiario</th>
                            <th class="text-left px-4 py-3">Apoyo</th>
                            <th class="text-center px-4 py-3">Documentos</th>
                            <th class="text-left px-4 py-3">Creado</th>
                            <th class="text-center px-4 py-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-pendientes">
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-center text-gray-500">Cargando pendientes...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Área de Carga de Documentos -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Cargar Documento Escaneado</h2>

            <form id="formCargarDocumento" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Entrada de Folio -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Folio (o escanear QR)
                        </label>
                        <input type="text" 
                               id="folioInput" 
                               name="folio"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Ej: 001-2026-TEP"
                               required>
                    </div>
                </div>

                <!-- Información Cargada Dinámicamente -->
                <div id="datosContainer" class="hidden space-y-4">
                    <!-- Datos del Beneficiario -->
                    <div class="p-4 bg-blue-50 rounded border border-blue-200">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">👤 Beneficiario</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-600 text-xs">Nombre:</p>
                                <p id="beneficiarioNombre" class="font-semibold text-gray-900">--</p>
                            </div>
                            <div>
                                <p class="text-gray-600 text-xs">CURP:</p>
                                <p id="beneficiarioCurp" class="font-mono text-gray-900 text-xs">--</p>
                            </div>
                            <div>
                                <p class="text-gray-600 text-xs">Email:</p>
                                <p id="beneficiarioEmail" class="text-gray-900 text-xs truncate">--</p>
                            </div>
                            <div>
                                <p class="text-gray-600 text-xs">Teléfono:</p>
                                <p id="beneficiarioTelefono" class="text-gray-900 text-xs">--</p>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Apoyo -->
                    <div class="p-4 bg-green-50 rounded border border-green-200">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">🎁 Apoyo</h3>
                        <div class="space-y-2">
                            <div>
                                <p class="text-gray-600 text-xs">Nombre:</p>
                                <p id="apoyoNombre" class="font-semibold text-gray-900 text-base">--</p>
                            </div>
                            <div>
                                <p class="text-gray-600 text-xs">Descripción:</p>
                                <p id="apoyoDescripcion" class="text-gray-900 text-sm line-clamp-2">--</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-gray-600 text-xs">Tipo:</p>
                                    <p id="apoyoTipo" class="text-gray-900 text-sm">--</p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-xs">Monto Máximo:</p>
                                    <p id="apoyoMonto" class="text-gray-900 font-semibold text-sm">--</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documentos Requeridos -->
                    <div class="p-4 bg-purple-50 rounded border border-purple-200">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">📋 Documentos Requeridos</h3>
                        <div id="documentosRequeridosList" class="space-y-2">
                            <p class="text-gray-600 text-sm italic">Cargando documentos...</p>
                        </div>
                    </div>

                    <!-- Áreas de Carga por Documento -->
                    <div id="areasCarguaDocumentos" class="space-y-4">
                        <!-- Se llena dinámicamente desde JavaScript -->
                    </div>
                </div>

                <!-- Spinner de carga -->
                <div id="loadingSpinner" class="hidden flex items-center justify-center p-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                    <span class="ml-3 text-gray-600">Buscando folio...</span>
                </div>

                <!-- Mensaje de error -->
                <div id="errorMessage" class="hidden p-4 bg-red-50 rounded border border-red-200">
                    <p id="errorText" class="text-red-700 text-sm"></p>
                </div>

                <!-- Botones de Envío (se muestran cuando hay documentos) -->
                <div id="botonesEnvio" class="hidden flex gap-4 mt-6">
                    <button type="button" onclick="limpiarCargas()" 
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Limpiar
                    </button>
                    <button type="submit" 
                            class="px-8 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold flex items-center ml-auto">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Cargar Documentos
                    </button>
                </div>
            </form>
        </div>

        <!-- Historial de Documentos Cargados Hoy -->
        @if($documentosHoy->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Documentos cargados hoy por {{ $adminNombre }}
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="text-left px-4 py-2">Folio</th>
                                <th class="text-left px-4 py-2">Tipo</th>
                                <th class="text-left px-4 py-2">Beneficiario</th>
                                <th class="text-left px-4 py-2">Hora</th>
                                <th class="text-left px-4 py-2">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documentosHoy as $doc)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 font-mono text-blue-600">{{ $doc->solicitud?->folio ?? 'N/A' }}</td>
                                    <td class="px-4 py-2 capitalize">{{ $doc->fk_id_tipo_doc ?? 'N/A' }}</td>
                                    <td class="px-4 py-2">{{ $doc->solicitud?->beneficiario?->nombre ?? 'N/A' }}</td>
                                    <td class="px-4 py-2">{{ $doc->fecha_carga?->format('H:i') ?? 'N/A' }}</td>
                                    <td class="px-4 py-2">
                                        @if($doc->estado_validacion === 'VERIFICADO')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">✓ Verificado</span>
                                        @elseif($doc->estado_validacion === 'RECHAZADO')
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">✗ Rechazado</span>
                                        @else
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">⏱ Pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    // Variables globales
    let folioActual = null;
    let datosDelFolio = null;
    let pendientesCargados = false;

    // Cargar pendientes al abrir la página
    document.addEventListener('DOMContentLoaded', cargarPendientes);

    // Event listeners
    document.getElementById('folioInput').addEventListener('blur', cargarDatosDelFolio);
    document.getElementById('folioInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            cargarDatosDelFolio();
        }
    });

    // Obtener token CSRF
    const getCsrfToken = () => {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    };

    // Cargar lista de pendientes
    async function cargarPendientes() {
        const tbody = document.getElementById('tbody-pendientes');
        const container = document.getElementById('tablaPendientesContainer');
        
        // Mostrar estado de carga
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                    <div class="flex items-center justify-center">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-yellow-600 mr-2"></div>
                        Cargando pendientes...
                    </div>
                </td>
            </tr>
        `;
        container.style.display = '';
        
        try {
            const url = '{{ url('/api/caso-a/pendientes-escaneo') }}';
            console.log('Fetching pendientes from:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            console.log('Pendientes result:', result);

            if (result.success && result.total > 0) {
                mostrarPendientes(result.pendientes);
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-4 py-4 text-center text-gray-500 italic">
                            No hay pendientes de escaneo en este momento
                        </td>
                    </tr>
                `;
            }
        } catch (error) {
            console.error('Error al cargar pendientes:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-4 py-4 text-center">
                        <p class="text-red-600 font-semibold">Error al cargar pendientes</p>
                        <p class="text-red-500 text-sm">${error.message}</p>
                        <button onclick="cargarPendientes()" class="mt-2 px-4 py-2 bg-yellow-600 text-white rounded text-sm hover:bg-yellow-700">
                            Reintentar
                        </button>
                    </td>
                </tr>
            `;
        }
    }

    function ocultarTablaPendientes() {
        // Ya no ocultar, simplemente mostrar mensaje
        const tbody = document.getElementById('tbody-pendientes');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-4 py-4 text-center text-gray-500 italic">
                    No hay pendientes de escaneo
                </td>
            </tr>
        `;
    }

    function mostrarPendientes(pendientes) {
        const container = document.getElementById('tablaPendientesContainer');
        const tbody = document.getElementById('tbody-pendientes');
        const badge = document.getElementById('badgePendientes');
        const contador = document.getElementById('contadorPendientes');

        // Limpiar tabla
        tbody.innerHTML = '';

        // Llenar tabla
        pendientes.forEach(p => {
            const row = document.createElement('tr');
            row.className = 'border-b hover:bg-gray-50 cursor-pointer transition';
            row.innerHTML = `
                <td class="px-4 py-3 font-mono font-semibold text-blue-600">${p.folio}</td>
                <td class="px-4 py-3 text-sm">${p.beneficiario_nombre || 'N/A'}</td>
                <td class="px-4 py-3 text-sm">${p.apoyo_nombre || 'N/A'}</td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        ${p.documentos_cargados}/${p.documentos_requeridos}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">${p.fecha_creacion}</td>
                <td class="px-4 py-3 text-center">
                    <button type="button" 
                            onclick="seleccionarPendiente('${p.folio}')"
                            class="inline-flex items-center px-3 py-1 bg-green-600 text-white text-xs rounded font-semibold hover:bg-green-700 transition">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Seleccionar
                    </button>
                </td>
            `;
            row.addEventListener('click', () => seleccionarPendiente(p.folio));
            tbody.appendChild(row);
        });

        // Actualizar badge y contador
        badge.textContent = `${pendientes.length} pendientes`;
        contador.textContent = pendientes.length;

        // Mostrar tabla
        container.classList.remove('hidden');
        pendientesCargados = true;
    }

    function seleccionarPendiente(folio) {
        // Establecer folio y cargar datos
        document.getElementById('folioInput').value = folio;
        cargarDatosDelFolio();
        
        // Scroll suave al formulario
        document.querySelector('.bg-white.rounded-lg.shadow-lg.p-8').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Cargar datos del folio vía API
    async function cargarDatosDelFolio() {
        const folio = document.getElementById('folioInput').value.trim();
        
        if (!folio) {
            ocultarDatos();
            return;
        }

        folioActual = folio;
        mostrarCarga();

        try {
            const baseUrl = '{{ url('/api/caso-a/folio') }}';
            const response = await fetch(`${baseUrl}/${folio}`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();

            if (result.success) {
                datosDelFolio = result;
                mostrarDatos(result);
                ocultarError();
            } else {
                mostrarError(result.error || 'Error al cargar datos del folio');
                ocultarDatos();
            }
        } catch (error) {
            mostrarError('Error de conexión: ' + error.message);
            ocultarDatos();
        } finally {
            ocultarCarga();
        }
    }

    function mostrarDatos(datos) {
        // Llenar datos del beneficiario
        document.getElementById('beneficiarioNombre').textContent = datos.beneficiario.nombre || '--';
        document.getElementById('beneficiarioCurp').textContent = datos.beneficiario.curp || '--';
        document.getElementById('beneficiarioEmail').textContent = datos.beneficiario.email || '--';
        document.getElementById('beneficiarioTelefono').textContent = datos.beneficiario.telefono || '--';

        // Llenar datos del apoyo
        document.getElementById('apoyoNombre').textContent = datos.apoyo.nombre || '--';
        document.getElementById('apoyoDescripcion').textContent = datos.apoyo.descripcion || '--';
        document.getElementById('apoyoTipo').textContent = datos.apoyo.tipo || '--';
        document.getElementById('apoyoMonto').textContent = '$ ' + (datos.apoyo.monto_maximo ? datos.apoyo.monto_maximo.toLocaleString('es-MX') : '0');

        // Llenar documentos requeridos
        const listaDocumentos = document.getElementById('documentosRequeridosList');
        listaDocumentos.innerHTML = '';

        // Generar áreas de carga por documento
        const areasCargas = document.getElementById('areasCarguaDocumentos');
        areasCargas.innerHTML = '';

        if (datos.documentos_requeridos && datos.documentos_requeridos.length > 0) {
            datos.documentos_requeridos.forEach(doc => {
                // 1. Agregar a lista de documentos
                const docDiv = document.createElement('div');
                docDiv.className = 'flex items-center p-2 bg-white rounded border-l-4 ' + 
                    (doc.ya_cargado ? 'border-green-500 opacity-60' : 'border-purple-500');
                
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'mr-3 w-4 h-4 text-green-600 rounded';
                checkbox.checked = doc.ya_cargado;
                checkbox.disabled = doc.ya_cargado;

                const label = document.createElement('div');
                label.className = 'flex-1';
                label.innerHTML = `
                    <p class="text-sm font-semibold text-gray-900">${doc.nombre_documento}</p>
                    <p class="text-xs text-gray-600">
                        ${doc.es_obligatorio ? '✓ Obligatorio' : '○ Opcional'} 
                        ${doc.ya_cargado ? ' • Ya cargado' : ''}
                    </p>
                `;

                docDiv.appendChild(checkbox);
                docDiv.appendChild(label);
                listaDocumentos.appendChild(docDiv);

                // 2. Crear área de carga para cada documento NO cargado
                if (!doc.ya_cargado) {
                    const areaCarga = document.createElement('div');
                    areaCarga.className = 'bg-white p-6 rounded-lg border-2 border-dashed border-green-300 hover:border-green-500 transition';
                    areaCarga.dataset.tipoDoc = doc.id_tipo_doc;
                    areaCarga.dataset.nombreDoc = doc.nombre_documento;
                    areaCarga.dataset.tiposPermitidos = doc.tipo_archivo_permitido;
                    areaCarga.dataset.pesoMaximo = doc.peso_maximo_mb;
                    
                    const inputFile = document.createElement('input');
                    inputFile.type = 'file';
                    inputFile.className = 'hidden';
                    
                    // Construir atributo accept de los tipos permitidos
                    const tiposPermitidos = (doc.tipo_archivo_permitido || 'pdf,jpg,jpeg,png').split(',').map(t => '.' + t.trim());
                    inputFile.accept = tiposPermitidos.join(',');
                    inputFile.dataset.tipoDoc = doc.id_tipo_doc;
                    inputFile.dataset.tiposPermitidos = doc.tipo_archivo_permitido;
                    inputFile.dataset.pesoMaximo = doc.peso_maximo_mb;
                    
                    const html = `
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">📄 ${doc.nombre_documento}</h4>
                        <div class="border-2 border-dashed border-green-300 bg-green-50 rounded-lg p-8 text-center cursor-pointer transition hover:border-green-500 hover:bg-green-100" 
                             ondrop="handleDrop(event, ${doc.id_tipo_doc})" 
                             ondragover="handleDragOver(event)" 
                             ondragleave="handleDragLeave(event)">
                            <svg class="w-12 h-12 mx-auto text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <p class="text-gray-700 font-semibold">Arrastra archivo aquí o haz clic</p>
                            <p class="text-sm text-gray-600 mt-1">${tiposPermitidos.join(', ').toUpperCase()} (máximo ${doc.peso_maximo_mb} MB)</p>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button type="button" 
                                    onclick="document.querySelector('input[data-tipo-doc=&quot;${doc.id_tipo_doc}&quot;]').click()" 
                                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition text-sm">
                                Seleccionar Archivo
                            </button>
                            <div class="flex-1 text-left" id="preview-${doc.id_tipo_doc}">
                                <p class="text-sm text-gray-500">Sin archivo seleccionado</p>
                            </div>
                        </div>
                    `;
                    
                    areaCarga.innerHTML = html;
                    areaCarga.appendChild(inputFile);
                    areasCargas.appendChild(areaCarga);
                    
                    // Agregar event listeners para el archivo
                    inputFile.addEventListener('change', (e) => {
                        handleFileSelect(e, doc.id_tipo_doc, doc.nombre_documento);
                    });
                }
            });

            document.getElementById('botonesEnvio').classList.remove('hidden');
        } else {
            listaDocumentos.innerHTML = '<p class="text-gray-600 text-sm italic">No hay documentos requeridos</p>';
            document.getElementById('botonesEnvio').classList.add('hidden');
        }

        // Mostrar el contenedor de datos
        document.getElementById('datosContainer').classList.remove('hidden');
    }

    function ocultarDatos() {
        document.getElementById('datosContainer').classList.add('hidden');
        document.getElementById('areasCarguaDocumentos').innerHTML = '';
        document.getElementById('botonesEnvio').classList.add('hidden');
        datosDelFolio = null;
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.target.closest('[ondrop]')?.classList.add('bg-green-200', 'border-green-500');
    }

    function handleDragLeave(e) {
        e.preventDefault();
        e.target.closest('[ondrop]')?.classList.remove('bg-green-200', 'border-green-500');
    }

    function handleDrop(e, tipoDoc) {
        e.preventDefault();
        e.stopPropagation();
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const input = document.querySelector(`input[data-tipo-doc="${tipoDoc}"]`);
            input.files = files;
            handleFileSelect({ target: input }, tipoDoc);
        }
    }

    function handleFileSelect(e, tipoDoc, nombreDoc) {
        const file = e.target.files[0];
        if (!file) return;

        const tiposPermitidos = e.target.dataset.tiposPermitidos || 'pdf,jpg,jpeg,png';
        const pesoMaximoMb = parseInt(e.target.dataset.pesoMaximo) || 5;
        const pesoMaximoBytes = pesoMaximoMb * 1024 * 1024;

        // Validar tipo de archivo
        const extension = file.name.split('.').pop().toLowerCase();
        const tiposArray = tiposPermitidos.split(',').map(t => t.trim().toLowerCase());
        
        if (!tiposArray.includes(extension)) {
            const previewDiv = document.getElementById(`preview-${tipoDoc}`);
            previewDiv.innerHTML = `<p class="text-sm text-red-600 font-semibold">✗ Tipo de archivo no permitido</p>
                                   <p class="text-xs text-red-500">Solo se permiten: ${tiposArray.join(', ').toUpperCase()}</p>`;
            e.target.value = '';
            return;
        }

        // Validar tamaño
        if (file.size > pesoMaximoBytes) {
            const previewDiv = document.getElementById(`preview-${tipoDoc}`);
            previewDiv.innerHTML = `<p class="text-sm text-red-600 font-semibold">✗ Archivo muy grande</p>
                                   <p class="text-xs text-red-500">Máximo permitido: ${pesoMaximoMb} MB</p>`;
            e.target.value = '';
            return;
        }

        // Mostrar preview
        const previewDiv = document.getElementById(`preview-${tipoDoc}`);
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (event) => {
                previewDiv.innerHTML = `<img src="${event.target.result}" class="max-h-48 rounded mt-2 mx-auto">`;
            };
            reader.readAsDataURL(file);
        } else {
            previewDiv.innerHTML = `<p class="text-sm text-green-600 font-semibold">✓ ${file.name}</p><p class="text-xs text-green-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>`;
        }
    }

    function limpiarCargas() {
        document.querySelectorAll('input[type="file"][data-tipo-doc]').forEach(input => {
            input.value = '';
            const tipoDoc = input.dataset.tipoDoc;
            document.getElementById(`preview-${tipoDoc}`).innerHTML = '<p class="text-sm text-gray-500">Sin archivo seleccionado</p>';
        });
    }

    function mostrarCarga() {
        document.getElementById('loadingSpinner').classList.remove('hidden');
    }

    function ocultarCarga() {
        document.getElementById('loadingSpinner').classList.add('hidden');
    }

    function mostrarError(mensaje) {
        document.getElementById('errorMessage').classList.remove('hidden');
        document.getElementById('errorText').textContent = mensaje;
    }

    function ocultarError() {
        document.getElementById('errorMessage').classList.add('hidden');
    }

    // Envío del formulario con múltiples documentos
    function validarArchivoAntesDeSendto(file, tipoDoc, datosDelFolio) {
        if (!datosDelFolio || !datosDelFolio.documentos_requeridos) {
            return { valido: true }; // Si no hay datos, permitir (el servidor hará validación)
        }

        // Encontrar la configuración del documento
        const docConfig = datosDelFolio.documentos_requeridos.find(doc => 
            doc.id_tipo_doc == tipoDoc
        );

        if (!docConfig) {
            return { valido: false, error: 'Tipo de documento no configurado' };
        }

        // Validar tipo de archivo
        if (docConfig.validar_tipo_archivo) {
            const tiposPermitidos = docConfig.tipo_archivo_permitido.split(',').map(t => t.trim().toLowerCase());
            const extension = file.name.split('.').pop().toLowerCase();
            
            if (!tiposPermitidos.includes(extension)) {
                return { 
                    valido: false, 
                    error: `Tipo no permitido. Aceptados: ${tiposPermitidos.join(', ').toUpperCase()}` 
                };
            }
        }

        // Validar peso
        const pesoMaximoMb = parseInt(docConfig.peso_maximo_mb) || 5;
        const pesoMaximoBytes = pesoMaximoMb * 1024 * 1024;
        
        if (file.size > pesoMaximoBytes) {
            return { 
                valido: false, 
                error: `Excede límite de ${pesoMaximoMb}MB (tamaño: ${(file.size / 1024 / 1024).toFixed(2)}MB)` 
            };
        }

        return { valido: true };
    }

    document.getElementById('formCargarDocumento').addEventListener('submit', async (e) => {
        e.preventDefault();

        const folio = document.getElementById('folioInput').value;
        const archivosSeleccionados = [];

        // Recopilar todos los archivos seleccionados
        document.querySelectorAll('input[type="file"][data-tipo-doc]').forEach(input => {
            if (input.files && input.files.length > 0) {
                archivosSeleccionados.push({
                    file: input.files[0],
                    tipoDoc: input.dataset.tipoDoc
                });
            }
        });

        if (archivosSeleccionados.length === 0) {
            alert('Por favor selecciona al menos un documento');
            return;
        }

        // Mostrar progreso
        const statusDiv = document.createElement('div');
        statusDiv.className = 'fixed bottom-4 right-4 bg-blue-500 text-white p-4 rounded-lg shadow-lg';
        statusDiv.innerHTML = `<p id="statusText">Enviando documentos... 0/${archivosSeleccionados.length}</p>`;
        document.body.appendChild(statusDiv);

        let exitosos = 0;
        let errores = [];

        // Enviar cada documento
        for (let i = 0; i < archivosSeleccionados.length; i++) {
            const { file, tipoDoc } = archivosSeleccionados[i];
            
            // Validar archivo antes de enviar
            const validacionPrevia = validarArchivoAntesDeSendto(file, tipoDoc, datosDelFolio);
            if (!validacionPrevia.valido) {
                errores.push(tipoDoc + ': ' + validacionPrevia.error);
                document.getElementById('statusText').innerHTML = `<p>${i + 1}/${archivosSeleccionados.length} - Error: ${validacionPrevia.error}</p>`;
                continue;
            }
            
            const formData = new FormData();
            formData.append('folio', folio);
            formData.append('tipo_documento', tipoDoc);
            formData.append('documento', file);

            try {
                const response = await fetch('{{ route("admin.caso-a.cargar-documento-momento-dos") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: formData,
                    credentials: 'include'
                });

                const result = await response.json();

                if (result.success) {
                    exitosos++;
                    document.getElementById('statusText').textContent = `Enviando documentos... ${i + 1}/${archivosSeleccionados.length} ✓`;
                } else {
                    errores.push(tipoDoc + ': ' + result.error);
                    document.getElementById('statusText').innerHTML = `<p>${i + 1}/${archivosSeleccionados.length} - Error: ${result.error}</p>`;
                }
            } catch (error) {
                errores.push(tipoDoc + ': ' + error.message);
                document.getElementById('statusText').innerHTML = `<p>Error: ${error.message}</p>`;
            }
        }

        // Mostrar resultado final
        setTimeout(() => {
            if (errores.length === 0) {
                statusDiv.className = 'fixed bottom-4 right-4 bg-green-500 text-white p-4 rounded-lg shadow-lg';
                statusDiv.innerHTML = `<p>✓ Todos los ${exitosos} documento(s) se cargaron exitosamente</p>`;
                setTimeout(() => {
                    document.getElementById('formCargarDocumento').reset();
                    limpiarCargas();
                    ocultarDatos();
                    location.reload();
                }, 2000);
            } else {
                statusDiv.className = 'fixed bottom-4 right-4 bg-red-500 text-white p-4 rounded-lg shadow-lg';
                statusDiv.innerHTML = `<p>✓ ${exitosos} cargados, ✗ ${errores.length} errores</p>`;
            }
        }, 500);
    });
</script>
@endsection
