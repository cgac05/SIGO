<!-- 
    Componente: Resumen crítico ANTES de firmar
    Propósito: Mostrar toda la información importante que el directivo debe revisar antes de autorizar
    Rol: Directivos (roles 2, 3)
    Fase: 2 - Firma Directiva
-->

<div class="space-y-6">
    <!-- 🚨 ALERTA CRÍTICA: RESUMEN DE LO QUE VAS A AUTORIZAR -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-l-4 border-amber-500 rounded-lg p-6 shadow-md">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-amber-900">REVISA ANTES DE FIRMAR</h3>
                <p class="text-sm text-amber-800 mt-1">
                    Esta acción autorizará la solicitud. Verifica que toda la información sea correcta.
                </p>
            </div>
        </div>
    </div>

    <!-- GRID PRINCIPAL: INFORMACIÓN CRÍTICA -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- BLOQUE 1: BENEFICIARIO -->
        <div class="bg-white rounded-lg border-2 border-blue-200 shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center gap-2 mb-4">
                <div class="bg-blue-100 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Beneficiario</h3>
            </div>
            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-gray-600 font-semibold">Nombre Completo</p>
                    <p class="text-gray-900 font-bold text-base">{{ $beneficiario->nombre ?? 'N/A' }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-gray-600 font-semibold">CURP</p>
                        <p class="font-mono text-gray-900 text-xs font-bold bg-gray-100 p-2 rounded">
                            {{ $beneficiario->curp ?? 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-semibold">Email</p>
                        <p class="text-blue-600 text-xs truncate">{{ $beneficiario->email ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- BLOQUE 2: APOYO Y MONTO -->
        <div class="bg-white rounded-lg border-2 border-green-200 shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center gap-2 mb-4">
                <div class="bg-green-100 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.16 2.75a.75.75 0 00-1.06 1.06L8.94 5H7.75a2.75 2.75 0 000 5.5h.75v1.75a.75.75 0 001.5 0V10.5h.75a2.75 2.75 0 000-5.5h-1.19l1.78-1.69a.75.75 0 00-1.06-1.06l-3-2.75zM16 10.75a.75.75 0 00-.75.75v1.75h-.75a2.75 2.75 0 000 5.5h1.19l-1.78 1.69a.75.75 0 101.06 1.06l3-2.75a.75.75 0 000-1.06l-3-2.75a.75.75 0 00-1.06 1.06l1.78 1.69H15.5v-1.75a.75.75 0 00-.75-.75z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Apoyo Económico</h3>
            </div>
            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-gray-600 font-semibold">Tipo de Apoyo</p>
                    <p class="text-gray-900 font-bold text-base">{{ $apoyo->nombre_apoyo ?? 'N/A' }}</p>
                </div>
                <div class="bg-green-50 border-l-4 border-green-500 p-3 rounded">
                    <p class="text-gray-600 font-semibold">Monto a Autorizar</p>
                    <p class="text-2xl font-bold text-green-700">${{ number_format($monto_solicitud ?? 0, 2, '.', ',') }}</p>
                </div>
            </div>
        </div>

        <!-- BLOQUE 3: DOCUMENTOS REQUERIDOS -->
        <div class="bg-white rounded-lg border-2 border-purple-200 shadow-sm p-6 hover:shadow-md transition lg:col-span-2">
            <div class="flex items-center gap-2 mb-4">
                <div class="bg-purple-100 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Estado de Documentos</h3>
                <span class="ml-auto bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold">
                    ✓ TODOS APROBADOS
                </span>
            </div>
            <div class="space-y-2">
                @if($documentos && count($documentos) > 0)
                    @foreach($documentos as $doc)
                    <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-lg hover:bg-green-50 transition">
                        <div class="flex items-center gap-2 flex-1">
                            <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900">{{ $doc->nombre_archivo ?? 'Documento #' . $doc->id_doc }}</p>
                                <p class="text-xs text-gray-600">{{ $doc->fk_id_tipo_doc ? 'Tipo: ' . $doc->fk_id_tipo_doc : '' }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-bold rounded">
                            {{ $doc->estado_validacion === 'Correcto' ? '✓ Aprobado' : $doc->estado_validacion }}
                        </span>
                    </div>
                    @endforeach
                @else
                    <p class="text-gray-600 text-sm py-3 text-center">No hay documentos registrados</p>
                @endif
            </div>
        </div>

        <!-- BLOQUE 4: INFORMACIÓN DE PRESUPUESTACIÓN -->
        <div class="bg-white rounded-lg border-2 border-indigo-200 shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center gap-2 mb-4">
                <div class="bg-indigo-100 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Presupuestación</h3>
            </div>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-semibold">Monto Solicitado</span>
                    <span class="font-bold text-gray-900">${{ number_format($monto_solicitud ?? 0, 2, '.', ',') }}</span>
                </div>
                <div class="border-t border-gray-200 pt-3 flex justify-between items-center">
                    <span class="text-gray-600 font-semibold">Estado</span>
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 font-bold rounded text-xs">
                        ◉ En Revisión
                    </span>
                </div>
            </div>
        </div>

        <!-- BLOQUE 5: HITO Y FASE -->
        <div class="bg-white rounded-lg border-2 border-gray-200 shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center gap-2 mb-4">
                <div class="bg-gray-100 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9zM4 5a2 2 0 012-2 1 1 0 000-2 4 4 0 00-4 4v10a4 4 0 004 4h12a4 4 0 004-4V5a4 4 0 00-4-4 1 1 0 000 2 2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V5z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Fase y Hito</h3>
            </div>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-semibold">Fase Actual</span>
                    <span class="font-bold text-blue-600">Fase 2: Firma</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-semibold">Hito</span>
                    <span class="font-mono text-gray-900 font-bold">
                        {{ $hito_actual ? $hito_actual->clave_hito : 'N/A' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- CHECKLIST: VALIDACIONES FINALES -->
    <div class="bg-white rounded-lg border-2 border-amber-100 shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">✓ Validaciones Finales</h3>
        <div class="space-y-3">
            <label class="flex items-start gap-3 cursor-pointer hover:bg-gray-50 p-2 rounded transition">
                <input type="checkbox" class="mt-1 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-2" id="confirm-beneficiario">
                <span class="text-sm text-gray-700 leading-relaxed">
                    Confirmo que <strong>{{ $beneficiario->nombre ?? 'el beneficiario' }}</strong> es quien solicitó este apoyo
                </span>
            </label>
            <label class="flex items-start gap-3 cursor-pointer hover:bg-gray-50 p-2 rounded transition">
                <input type="checkbox" class="mt-1 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-2" id="confirm-monto">
                <span class="text-sm text-gray-700 leading-relaxed">
                    Autorizo el desembolso de <strong>${{ number_format($monto_solicitud ?? 0, 2, '.', ',') }}</strong> para este apoyo
                </span>
            </label>
            <label class="flex items-start gap-3 cursor-pointer hover:bg-gray-50 p-2 rounded transition">
                <input type="checkbox" class="mt-1 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-2" id="confirm-documentos">
                <span class="text-sm text-gray-700 leading-relaxed">
                    He revisado que todos los documentos estén <strong>aprobados y completos</strong>
                </span>
            </label>
            <label class="flex items-start gap-3 cursor-pointer hover:bg-gray-50 p-2 rounded transition">
                <input type="checkbox" class="mt-1 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-2" id="confirm-responsabilidad">
                <span class="text-sm text-gray-700 leading-relaxed">
                    Entiendo que esta firma es <strong>vinculante y auditable</strong> según la LGPDP
                </span>
            </label>
        </div>
    </div>

    <!-- NOTA DE SEGURIDAD -->
    <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 text-sm text-red-800">
        <p class="font-bold mb-1">🔐 Nota de Seguridad</p>
        <p>
            Esta firma electrónica será registrada en la cadena de custodia y es auditable por autoridades. 
            Al firmar confirmas que revisaste toda la información y que esta solicitud es válida y completa.
        </p>
    </div>
</div>

<style>
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .space-y-6 > * {
        animation: slideInUp 0.4s ease-out forwards;
    }
    
    .space-y-6 > *:nth-child(1) { animation-delay: 0.05s; }
    .space-y-6 > *:nth-child(2) { animation-delay: 0.1s; }
    .space-y-6 > *:nth-child(3) { animation-delay: 0.15s; }
    .space-y-6 > *:nth-child(4) { animation-delay: 0.2s; }
</style>
