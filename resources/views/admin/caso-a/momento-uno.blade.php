@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 flex items-center">
                        <svg class="w-10 h-10 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Digitación Presencial
                    </h1>
                    <p class="text-gray-600 mt-2 text-lg">Momento 1: Beneficiario presente - Registro y generación de folio</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-600">Hora: <span id="horaActual" class="font-mono font-semibold text-gray-900"></span></div>
                    <div class="text-sm text-gray-600 mt-1">Administrador: <span class="font-semibold text-gray-900">{{ Auth::user()->name ?? 'Admin' }}</span></div>
                </div>
            </div>
        </div>

        <!-- Notificaciones -->
        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                    </svg>
                    <div>
                        <h3 class="text-red-800 font-semibold">Errores encontrados:</h3>
                        <ul class="text-red-700 list-disc pl-5 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start">
                <svg class="w-5 h-5 text-green-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                </svg>
                <p class="text-green-800 font-semibold">{{ session('success') }}</p>
            </div>
        @endif

        <!-- Formulario Principal -->
        <form action="{{ route('admin.caso-a.guardar-momento-uno') }}" method="POST" id="formMomentoUno" class="space-y-6">
            @csrf

            <!-- Paso 1: Búsqueda y Datos del Beneficiario -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex items-center">
                        <div class="bg-white bg-opacity-20 rounded-lg p-3 mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">Paso 1: Datos del Beneficiario</h2>
                            <p class="text-blue-100 text-sm mt-1">Busca e identifica al beneficiario presente</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Búsqueda por CURP -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-3">
                                <span class="flex items-center">
                                    <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-2">1</span>
                                    Ingresa CURP del Beneficiario
                                </span>
                            </label>
                            <input type="text" 
                                   id="beneficiarioBusqueda" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition uppercase tracking-widest font-mono"
                                   placeholder="AAAA000000MMMNNNRR0"
                                   autocomplete="off"
                                   maxlength="18"
                                   pattern="[A-Z0-9]{18}"
                                   required>
                            <p class="text-xs text-gray-600 mt-2">Formato: 18 caracteres alfanuméricos (mayúsculas)</p>
                            <div id="resultadosBusqueda" class="mt-3 hidden">
                                <ul id="listaBeneficiarios" class="border-2 border-gray-300 rounded-lg max-h-56 overflow-y-auto bg-white shadow-lg">
                                </ul>
                            </div>
                            <div id="errorCurp" class="mt-2 p-3 bg-red-50 border border-red-300 rounded-lg hidden">
                                <p class="text-sm text-red-700"><strong>⚠️ CURP inválido</strong> - Debe ser 18 caracteres alfanuméricos</p>
                            </div>
                            <div id="noBuscado" class="mt-2 p-3 bg-blue-50 border border-blue-300 rounded-lg hidden">
                                <p class="text-sm text-blue-700">ℹ️ No registrado. Completa los datos manualmente →</p>
                            </div>
                        </div>

                        <!-- Captura de Datos (Siempre Visible) -->
                        <div class="lg:col-span-2">
                            <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-lg p-6 border-2 border-amber-200">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">
                                        <span id="etiquetaDatos">📋 Datos del Beneficiario</span>
                                    </h3>
                                    <span id="estatusDatos" class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full">⚡ Editable</span>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-900 mb-2 uppercase tracking-wide">Nombre Completo *</label>
                                        <input type="text" 
                                               id="manual_nombre" 
                                               name="manual_nombre"
                                               class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                               placeholder="Ej: Juan Pérez García"
                                               required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-900 mb-2 uppercase tracking-wide">CURP *</label>
                                        <input type="text" 
                                               id="manual_curp" 
                                               name="manual_curp"
                                               class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition uppercase tracking-widest font-mono"
                                               placeholder="AAAA000000MMMNNNRR0"
                                               pattern="[A-Z0-9]{18}"
                                               maxlength="18"
                                               required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-900 mb-2 uppercase tracking-wide">Email</label>
                                        <input type="email" 
                                               id="manual_email" 
                                               name="manual_email"
                                               class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                               placeholder="ejemplo@correo.com">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-900 mb-2 uppercase tracking-wide">Teléfono</label>
                                        <input type="tel" 
                                               id="manual_telefono" 
                                               name="manual_telefono"
                                               class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                               placeholder="(123) 456-7890"
                                               maxlength="14">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="beneficiario_id" name="beneficiario_id">
                    <input type="hidden" id="es_beneficiario_registrado" name="es_beneficiario_registrado" value="0">
                </div>
            </div>

            <!-- Paso 2: Selección de Apoyo -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                    <div class="flex items-center">
                        <div class="bg-white bg-opacity-20 rounded-lg p-3 mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">Paso 2: Seleccionar Apoyo</h2>
                            <p class="text-green-100 text-sm mt-1">Elige el programa de apoyo a solicitar</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    @if($apoyos->count() === 0)
                        <div class="bg-yellow-50 border-2 border-yellow-200 rounded-lg p-6 text-center">
                            <svg class="w-12 h-12 text-yellow-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M6.458 20H15a2 2 0 002-2V6a2 2 0 00-2-2H6.458c.5-1 2-4 2-4s-3.5 1-3.5 4"/>
                            </svg>
                            <p class="text-yellow-800 font-semibold">No hay apoyos disponibles en este momento</p>
                            <p class="text-yellow-700 text-sm mt-1">Actualmente no hay programas en período de recepción de documentación</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Lista de Apoyos Izquierda -->
                            <div class="lg:col-span-1">
                                <label class="block text-sm font-semibold text-gray-900 mb-4">
                                    <span class="flex items-center">
                                        <span class="bg-green-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-2">2</span>
                                        Apoyos Disponibles
                                    </span>
                                </label>
                                <div class="space-y-2 max-h-96 overflow-y-auto pr-2">
                                    @foreach($apoyos as $apoyo)
                                        <label class="apoyo-item cursor-pointer group" data-apoyo-id="{{ $apoyo->id_apoyo }}" data-apoyo-json="{{ json_encode($apoyo) }}" data-documentos="{{ json_encode($apoyo->documentos_requeridos) }}">
                                            <input type="radio" 
                                                   name="apoyo_id" 
                                                   value="{{ $apoyo->id_apoyo }}"
                                                   class="hidden apoyo-radio"
                                                   required>
                                            <div class="p-4 border-2 border-gray-200 rounded-lg hover:border-green-400 group-hover:bg-green-50 transition-all duration-200">
                                                <div class="flex items-start">
                                                    <div class="w-5 h-5 border-2 border-gray-300 rounded-full mt-0.5 mr-3 flex items-center justify-center flex-shrink-0 group-hover:border-green-500">
                                                        <div class="w-2 h-2 bg-transparent rounded-full group-hover:bg-green-500 transition"></div>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <h3 class="font-bold text-gray-900 text-sm group-hover:text-green-700 transition line-clamp-2">{{ $apoyo->nombre_apoyo }}</h3>
                                                        <p class="text-xs text-gray-600 mt-1">{{ $apoyo->tipo_apoyo }}</p>
                                                        <div class="mt-2 pt-2 border-t border-gray-200">
                                                            <p class="text-xs text-gray-700"><strong>${{ number_format($apoyo->monto_maximo, 0) }}</strong></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Detalles Apoyo Derecha -->
                            <div id="detallesApoyo" class="lg:col-span-2 hidden">
                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border-2 border-green-200">
                                    <!-- Encabezado -->
                                    <div class="mb-6">
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 id="nombreApoyo" class="text-2xl font-bold text-gray-900"></h3>
                                            <span id="idApoyo" class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full">ID: --</span>
                                        </div>
                                        <p id="tipoApoyo" class="text-sm text-gray-600 uppercase tracking-wide"></p>
                                    </div>

                                    <!-- Información Económica -->
                                    <div class="grid grid-cols-3 gap-4 mb-6 pb-6 border-b-2 border-green-200">
                                        <div class="bg-white rounded-lg p-4 text-center">
                                            <p class="text-xs text-gray-600 uppercase tracking-wide mb-1">Monto Máximo</p>
                                            <p id="montoMaximo" class="text-2xl font-bold text-green-600">$0</p>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 text-center">
                                            <p class="text-xs text-gray-600 uppercase tracking-wide mb-1">Cupo Límite</p>
                                            <p id="cupoLimite" class="text-2xl font-bold text-blue-600">0</p>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 text-center">
                                            <p class="text-xs text-gray-600 uppercase tracking-wide mb-1">Ya Aprobados</p>
                                            <p id="totalAprobados" class="text-2xl font-bold text-purple-600">0</p>
                                        </div>
                                    </div>

                                    <!-- Hitos Timeline -->
                                    <div>
                                        <h4 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wide">Hitos y Etapas</h4>
                                        <div id="hitosTimeline" class="space-y-3">
                                            <!-- Populated by JS -->
                                        </div>
                                    </div>

                                    <!-- Mensaje de Información -->
                                    <div class="mt-6 p-4 bg-green-100 rounded-lg border border-green-300">
                                        <p class="text-sm text-green-800">
                                            <strong>✓ Este apoyo está en período de recepción de documentación</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Paso 3: Documentos Requeridos -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                    <div class="flex items-center">
                        <div class="bg-white bg-opacity-20 rounded-lg p-3 mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">Paso 3: Documentos Recibidos</h2>
                            <p class="text-purple-100 text-sm mt-1">Verifica los documentos físicos entregados</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div>
                            <div id="documentosContainer" class="space-y-3">
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg border-2 border-gray-200">
                                    <p class="text-sm text-gray-600 italic">Selecciona un apoyo para ver documentos requeridos</p>
                                </div>
                            </div>

                            <div class="mt-4 p-4 bg-gradient-to-r from-purple-100 to-pink-100 rounded-lg border-2 border-purple-200">
                                <p class="text-xs text-gray-600 uppercase tracking-wide mb-1">Documentos Verificados</p>
                                <p class="text-3xl font-bold text-purple-600"><span id="countDocumentos">0</span> / <span id="totalDocumentos">0</span></p>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-lg p-5 border-2 border-purple-200">
                            <h3 class="font-bold text-gray-900 mb-4">ℹ️ Información de Documentos</h3>
                            <div class="space-y-3 text-sm text-gray-700">
                                <div class="flex items-start">
                                    <span class="bg-purple-200 text-purple-700 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">✓</span>
                                    <span><strong>Cédula de Identidad:</strong> Documento oficial de identificación</span>
                                </div>
                                <div class="flex items-start">
                                    <span class="bg-purple-200 text-purple-700 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">✓</span>
                                    <span><strong>RFC:</strong> Registro Federal de Contribuyentes</span>
                                </div>
                                <div class="flex items-start">
                                    <span class="bg-purple-200 text-purple-700 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">✓</span>
                                    <span><strong>Comprobante de Domicilio:</strong> Factura, recibo u otro comprobante reciente</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paso 4: Datos Administrativos -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-amber-600 to-orange-600 px-6 py-4">
                    <div class="flex items-center">
                        <div class="bg-white bg-opacity-20 rounded-lg p-3 mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">Paso 4: Información Administrativa</h2>
                            <p class="text-amber-100 text-sm mt-1">Completa los datos de registro</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Capturar nombre del beneficiario seleccionado -->
                    <input type="hidden" id="nombre_beneficiario_input" name="nombre_beneficiario">

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-3">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5z"/>
                                    </svg>
                                    Observaciones (Notas Administrativas)
                                </span>
                            </label>
                            <textarea name="notas" 
                                      class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition h-28 resize-none"
                                      placeholder="Anotaciones administrativas sobre la entrega presencial (opcional)"></textarea>
                            <p class="text-xs text-gray-600 mt-2">Ej: Observaciones especiales, documentos incompletos, estado del beneficiario, etc.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex justify-between gap-4 items-center">
                <a href="{{ route('admin.solicitudes.index') }}" class="inline-flex items-center px-6 py-3 border-2 border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition font-semibold">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Cancelar
                </a>

                <button type="submit" class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:shadow-lg transition font-bold">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Crear Expediente y Generar Folio
                </button>
            </div>
        </form>

        <!-- Historial de Expedientes Hoy -->
        @if($expedientesHoy && $expedientesHoy->count() > 0)
            <div class="mt-12 bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-slate-700 to-slate-800 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        Historial de Hoy
                    </h2>
                </div>

                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-100 border-b-2 border-gray-300">
                                    <th class="text-left px-4 py-3 font-bold text-gray-700 text-sm">Folio</th>
                                    <th class="text-left px-4 py-3 font-bold text-gray-700 text-sm">Beneficiario</th>
                                    <th class="text-left px-4 py-3 font-bold text-gray-700 text-sm">Apoyo</th>
                                    <th class="text-left px-4 py-3 font-bold text-gray-700 text-sm">Hora</th>
                                    <th class="text-left px-4 py-3 font-bold text-gray-700 text-sm">Estado</th>
                                    <th class="text-center px-4 py-3 font-bold text-gray-700 text-sm">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expedientesHoy as $exp)
                                    <tr class="border-b hover:bg-blue-50 transition">
                                        <td class="px-4 py-3 font-mono text-blue-600 font-bold">{{ $exp->folio }}</td>
                                        <td class="px-4 py-3 text-gray-900">{{ $exp->solicitud?->beneficiario?->nombre_completo ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $exp->solicitud?->apoyo?->nombre_apoyo ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-gray-600 text-sm">{{ $exp->fecha_creacion?->format('H:i') }}</td>
                                        <td class="px-4 py-3"><span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-3 py-1 rounded-full">Pendiente Momento 2</span></td>
                                        <td class="px-4 py-3 text-center">
                                            <a href="{{ route('admin.caso-a.resumen-momento-uno', $exp->folio) }}" 
                                               class="inline-flex items-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition text-sm font-semibold">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Ver Ticket
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                            <p class="text-sm">No hay expedientes registrados aún hoy</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    // Validar CURP con regex
    const regexCURP = /^[A-Z0-9]{18}$/;

    document.getElementById('beneficiarioBusqueda').addEventListener('input', async function(e) {
        const curp = e.target.value.toUpperCase();
        document.getElementById('beneficiarioBusqueda').value = curp;
        
        const errorDiv = document.getElementById('errorCurp');
        const noBuscadoDiv = document.getElementById('noBuscado');

        if (curp.length === 0) {
            document.getElementById('resultadosBusqueda').classList.add('hidden');
            errorDiv.classList.add('hidden');
            noBuscadoDiv.classList.add('hidden');
            // Desbloquear campos
            desbloquearCampos();
            return;
        }

        if (!regexCURP.test(curp)) {
            if (curp.length === 18) {
                errorDiv.classList.remove('hidden');
            } else {
                errorDiv.classList.add('hidden');
            }
            document.getElementById('resultadosBusqueda').classList.add('hidden');
            return;
        }

        errorDiv.classList.add('hidden');

        try {
            const response = await fetch(`/api/beneficiarios/buscar?q=${encodeURIComponent(curp)}`);
            const data = await response.json();
            
            const lista = document.getElementById('listaBeneficiarios');
            lista.innerHTML = '';
            
            if (data.length === 0) {
                // No encontrado → desbloquear form manual
                noBuscadoDiv.classList.remove('hidden');
                desbloquearCampos();
                document.getElementById('es_beneficiario_registrado').value = '0';
                
                // Prellenar CURP en formulario manual
                document.getElementById('manual_curp').value = curp;
            } else {
                // Encontrado → mostrar en dropdown
                noBuscadoDiv.classList.add('hidden');
                data.forEach(b => {
                    const li = document.createElement('li');
                    li.className = 'px-4 py-3 hover:bg-blue-50 cursor-pointer border-b transition';
                    li.innerHTML = `
                        <div class="font-semibold text-gray-900">${b.nombre_completo}</div>
                        <div class="text-xs text-gray-600">${b.curp}</div>
                    `;
                    li.onclick = () => seleccionarBeneficiario(b);
                    lista.appendChild(li);
                });
                document.getElementById('resultadosBusqueda').classList.remove('hidden');
            }
        } catch(e) {
            console.error('Error:', e);
        }
    });

    function desbloquearCampos() {
        document.getElementById('manual_nombre').disabled = false;
        document.getElementById('manual_curp').disabled = false;
        document.getElementById('manual_email').disabled = false;
        document.getElementById('manual_telefono').disabled = false;
        
        document.getElementById('beneficiarioBusqueda').readOnly = false;
        document.getElementById('beneficiarioBusqueda').style.backgroundColor = '#ffffff';
        document.getElementById('beneficiarioBusqueda').style.cursor = 'text';
        
        document.getElementById('etiquetaDatos').innerHTML = '📋 Datos del Beneficiario';
        document.getElementById('estatusDatos').className = 'bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full';
        document.getElementById('estatusDatos').innerHTML = '⚡ Editable';

        document.getElementById('beneficiario_id').value = '';
        document.getElementById('es_beneficiario_registrado').value = '0';
    }

    function bloquearCampos() {
        document.getElementById('manual_nombre').disabled = true;
        document.getElementById('manual_curp').disabled = true;
        document.getElementById('manual_email').disabled = true;
        document.getElementById('manual_telefono').disabled = true;
        
        document.getElementById('beneficiarioBusqueda').readOnly = true;
        document.getElementById('beneficiarioBusqueda').style.backgroundColor = '#f3f4f6';
        document.getElementById('beneficiarioBusqueda').style.cursor = 'not-allowed';
        
        document.getElementById('etiquetaDatos').innerHTML = '✓ Datos del Sistema';
        document.getElementById('estatusDatos').className = 'bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full';
        document.getElementById('estatusDatos').innerHTML = '🔒 Bloqueado';
    }

    function seleccionarBeneficiario(beneficiario) {
        const esRegistrado = Boolean(beneficiario.fk_id_usuario);

        document.getElementById('beneficiario_id').value = esRegistrado ? beneficiario.fk_id_usuario : '';
        document.getElementById('es_beneficiario_registrado').value = esRegistrado ? '1' : '0';
        
        // Llenar campos
        document.getElementById('manual_nombre').value = beneficiario.nombre_completo;
        document.getElementById('manual_curp').value = beneficiario.curp || '';
        document.getElementById('manual_email').value = beneficiario.email || '';
        document.getElementById('manual_telefono').value = beneficiario.telefono || '';

        if (esRegistrado) {
            // Bloquear campos
            bloquearCampos();

            // Bloquear búsqueda de CURP (readonly) - no puede recapturar
            document.getElementById('beneficiarioBusqueda').readOnly = true;
            document.getElementById('beneficiarioBusqueda').style.backgroundColor = '#f3f4f6';
            document.getElementById('beneficiarioBusqueda').style.cursor = 'not-allowed';
            document.getElementById('beneficiarioBusqueda').title = 'CURP capturado - Click derecho para limpiar si es necesario';
        } else {
            desbloquearCampos();
            document.getElementById('beneficiarioBusqueda').title = 'Beneficiario no vinculado a un usuario del sistema';
        }
        
        document.getElementById('resultadosBusqueda').classList.add('hidden');
        document.getElementById('noBuscado').classList.add('hidden');
        document.getElementById('beneficiarioBusqueda').value = beneficiario.curp;
    }

    // Permitir limpiar búsqueda con Ctrl+Shift+C
    document.getElementById('beneficiarioBusqueda').addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'C') {
            e.preventDefault();
            // Limpiar búsqueda
            document.getElementById('beneficiarioBusqueda').readOnly = false;
            document.getElementById('beneficiarioBusqueda').style.backgroundColor = '#ffffff';
            document.getElementById('beneficiarioBusqueda').style.cursor = 'text';
            document.getElementById('beneficiarioBusqueda').value = '';
            document.getElementById('beneficiario_id').value = '';
            document.getElementById('es_beneficiario_registrado').value = '0';
            
            // Limpiar campos
            document.getElementById('manual_nombre').value = '';
            document.getElementById('manual_curp').value = '';
            document.getElementById('manual_email').value = '';
            document.getElementById('manual_telefono').value = '';
            
            // Desbloq all
            desbloquearCampos();
            
            document.getElementById('resultadosBusqueda').classList.add('hidden');
            document.getElementById('noBuscado').classList.add('hidden');
            document.getElementById('errorCurp').classList.add('hidden');
            
            document.getElementById('beneficiarioBusqueda').focus();
        }
    });

    // Mapa de documentos por tipo de apoyo (customizable por backend)
    const documentosPorApoyo = {
        'default': [
            { id: 'cedula', nombre: 'Cédula de Identidad', icono: '📋' },
            { id: 'comprobante_domicilio', nombre: 'Comprobante de Domicilio', icono: '🏠' },
            { id: 'rfc', nombre: 'RFC (Registro Federal)', icono: '🔖' },
            { id: 'certificado_estudios', nombre: 'Certificado de Estudios', icono: '🎓' },
        ]
    };

    // Actualizar hora
    function actualizarHora() {
        const ahora = new Date();
        document.getElementById('horaActual').textContent = ahora.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
    }
    actualizarHora();
    setInterval(actualizarHora, 1000);

    // Cambio de apoyo con radio buttons
    document.querySelectorAll('.apoyo-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const item = document.querySelector(`[data-apoyo-id="${this.value}"]`);
            const apoyo = JSON.parse(item.dataset.apoyoJson);
            
            // Actualizar estilos de items
            document.querySelectorAll('.apoyo-item').forEach(i => {
                const input = i.querySelector('input');
                if (input.checked) {
                    i.querySelector('div').classList.add('border-green-500', 'bg-green-50', 'shadow-md');
                    i.querySelector('div').classList.remove('border-gray-200');
                } else {
                    i.querySelector('div').classList.remove('border-green-500', 'bg-green-50', 'shadow-md');
                    i.querySelector('div').classList.add('border-gray-200');
                }
            });

            // Mostrar detalles
            mostrarDetallesApoyo(apoyo);
            actualizarDocumentos(this.value);
        });
    });

    function mostrarDetallesApoyo(apoyo) {
        document.getElementById('nombreApoyo').textContent = apoyo.nombre_apoyo;
        document.getElementById('idApoyo').textContent = 'ID: ' + apoyo.id_apoyo;
        document.getElementById('tipoApoyo').textContent = apoyo.tipo_apoyo;
        document.getElementById('montoMaximo').textContent = '$' + new Intl.NumberFormat('es-MX').format(apoyo.monto_maximo);
        document.getElementById('cupoLimite').textContent = apoyo.cupo_limite;
        document.getElementById('totalAprobados').textContent = apoyo.total_aprobadas || 0;

        // Generar timeline de hitos
        const hitosTimeline = document.getElementById('hitosTimeline');
        hitosTimeline.innerHTML = '';

        if (apoyo.hitos && apoyo.hitos.length > 0) {
            apoyo.hitos.forEach((hito, index) => {
                const ahora = new Date();
                const inicio = new Date(hito.fecha_inicio);
                const fin = new Date(hito.fecha_fin);
                const estaActivo = ahora >= inicio && ahora <= fin;
                const esRecepcion = hito.clave_hito === 'RECEPCION';

                let statusBg = 'bg-gray-100 border-gray-300';
                let statusText = 'text-gray-700';
                let statusIcon = '○';

                if (estaActivo) {
                    statusBg = 'bg-green-100 border-green-400';
                    statusText = 'text-green-700';
                    statusIcon = '●';
                } else if (ahora < inicio) {
                    statusBg = 'bg-blue-100 border-blue-300';
                    statusText = 'text-blue-700';
                    statusIcon = '◊';
                }

                const huellaRecepcion = esRecepcion ? ' <span class="ml-2 inline-block bg-green-500 text-white text-xs font-bold px-2 py-1 rounded">RECEPCIÓN</span>' : '';

                hitosTimeline.innerHTML += `
                    <div class="flex items-start pb-3 ${index < apoyo.hitos.length - 1 ? 'border-b border-green-200' : ''}">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full ${statusBg} border-2 flex items-center justify-center text-sm font-bold ${statusText}">
                            ${statusIcon}
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="font-semibold text-gray-900 text-sm">${hito.nombre_hito}${huellaRecepcion}</p>
                            <p class="text-xs text-gray-600 mt-1">
                                ${inicio.toLocaleDateString('es-MX')} → ${fin.toLocaleDateString('es-MX')}
                            </p>
                        </div>
                    </div>
                `;
            });
        }

        document.getElementById('detallesApoyo').classList.remove('hidden');
    }

    function actualizarDocumentos(apoyo_id) {
        // Obtener documentos del apoyo seleccionado
        const item = document.querySelector(`[data-apoyo-id="${apoyo_id}"]`);
        const documentosJson = item?.dataset.documentos;
        const documentos = documentosJson ? JSON.parse(documentosJson) : [];

        const container = document.getElementById('documentosContainer');
        
        if (documentos.length === 0) {
            container.innerHTML = '<div class="p-4 bg-yellow-50 border-2 border-yellow-200 rounded-lg"><p class="text-sm text-yellow-700">Este apoyo no tiene documentos configurados</p></div>';
            document.getElementById('totalDocumentos').textContent = '0';
            document.getElementById('countDocumentos').textContent = '0';
            return;
        }

        container.innerHTML = documentos.map(doc => {
            const indicador = doc.es_obligatorio ? '<span class="text-red-600 font-bold">*</span>' : '';
            return `
            <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-purple-400 hover:bg-purple-50 cursor-pointer transition">
                <input type="checkbox" name="documentos_listados[]" value="${doc.id_tipo_doc}" class="documentoCheckbox rounded-lg w-5 h-5 text-purple-600" ${doc.es_obligatorio ? 'required' : ''}>
                <span class="ml-3 text-gray-900 font-medium">📄 ${doc.nombre_documento} ${indicador}</span>
            </label>
            `;
        }).join('');

        document.querySelectorAll('.documentoCheckbox').forEach(cb => {
            cb.addEventListener('change', actualizarContador);
        });

        document.getElementById('totalDocumentos').textContent = documentos.length;
        actualizarContador();
    }

    function actualizarContador() {
        const total = document.querySelectorAll('.documentoCheckbox').length;
        const checkeados = document.querySelectorAll('.documentoCheckbox:checked').length;
        document.getElementById('countDocumentos').textContent = checkeados;
        document.getElementById('totalDocumentos').textContent = total;
    }

    // Validación del formulario
    document.getElementById('formMomentoUno').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const esRegistrado = document.getElementById('es_beneficiario_registrado').value === '1';
        const beneficiarioId = document.getElementById('beneficiario_id').value;
        const manualNombre = document.getElementById('manual_nombre').value.trim();
        const apoyoId = document.querySelector('input[name="apoyo_id"]:checked')?.value;
        const documentosSeleccionados = Array.from(document.querySelectorAll('input[name="documentos_listados[]"]:checked')).map(c => c.value);
        const notas = document.getElementById('notas')?.value || '';

        // Validar beneficiario
        if (esRegistrado) {
            if (!beneficiarioId) {
                alert('❌ Error: Selecciona un beneficiario registrado');
                return;
            }
        } else {
            if (!manualNombre) {
                alert('❌ Error: Ingresa el nombre completo del beneficiario');
                return;
            }
        }

        // Validar apoyo
        if (!apoyoId) {
            alert('❌ Error: Selecciona un apoyo');
            return;
        }

        // Validar documentos
        if (documentosSeleccionados.length === 0) {
            alert('❌ Error: Selecciona al menos un documento');
            return;
        }

        // Preparar datos
        const formData = new FormData(this);
        formData.set('es_beneficiario_registrado', esRegistrado ? '1' : '0');
        if (esRegistrado) formData.set('beneficiario_id', beneficiarioId);
        if (!esRegistrado) formData.set('manual_nombre', manualNombre);
        
        // Agregar documentos como array (no como JSON string)
        documentosSeleccionados.forEach(doc => {
            formData.append('documentos_listados[]', doc);
        });

        // Enviar
        fetch('{{ route("admin.caso-a.guardar-momento-uno") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(`✅ Expediente creado\n📋 Folio: ${data.folio}\n🔑 Clave: ${data.clave_acceso}`);
                window.location.href = '{{ route("admin.caso-a.momento-uno") }}';
            } else {
                alert('❌ Error: ' + (data.message || 'No se pudo guardar'));
            }
        })
        .catch(e => {
            console.error(e);
            alert('❌ Error de conexión');
        });
    });

    // Formateo de teléfono: (XXX) XXX-XXXX
    const inputTelefono = document.getElementById('manual_telefono');
    
    inputTelefono.addEventListener('input', function (e) {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
        
        if (!x[2]) {
            e.target.value = x[1] ? `(${x[1]}` : '';
        } else {
            e.target.value = x[3] ? `(${x[1]}) ${x[2]}-${x[3]}` : `(${x[1]}) ${x[2]}`;
        }
    });
    
    inputTelefono.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && e.target.value.length === 1) {
            e.target.value = '';
        }
    });
</script>
@endsection
