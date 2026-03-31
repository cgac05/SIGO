@props(['folio', 'tipo' => 'aprobacion'])

<!-- Modal de Re-autenticación para Firma Electrónica -->
<div id="firma-modal-{{ $folio }}" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center" x-data="firmaModalData()">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                @if($tipo === 'aprobacion')
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Confirmar Firma Digital
                @else
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                    Rechazar Solicitud
                @endif
            </h3>
        </div>

        <!-- Body -->
        <form id="firma-form-{{ $folio }}" method="POST" 
              action="@if($tipo === 'aprobacion'){{ route('solicitudes.proceso.firma-directiva') }}@else{{ route('solicitudes.proceso.rechazar-solicitud') }}@endif"
              class="p-6 space-y-4">
            @csrf

            <!-- Folio Input -->
            <input type="hidden" name="folio" value="{{ $folio }}">

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-900">
                    @if($tipo === 'aprobacion')
                        Ingresa tu contraseña para <strong>autorizar</strong> esta solicitud. Esta acción es <strong>irreversible</strong>.
                    @else
                        Ingresa tu contraseña para <strong>rechazar</strong> esta solicitud. Se notificará automáticamente al beneficiario.
                    @endif
                </p>
            </div>

            <!-- Password Field -->
            <div>
                <label for="password-{{ $folio }}" class="block text-sm font-semibold text-slate-900 mb-2">
                    Contraseña <span class="text-red-600">*</span>
                </label>
                <div class="relative">
                    <input type="password" 
                           id="password-{{ $folio }}" 
                           name="password" 
                           required 
                           minlength="8"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Ingresa tu contraseña"
                           autocomplete="current-password">
                    <button type="button" class="absolute right-3 top-2.5 text-slate-500 hover:text-slate-700" onclick="togglePasswordVisibility(this, 'password-{{ $folio }}')">
                        <svg class="h-5 w-5 hidden" data-show-icon fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                        </svg>
                        <svg class="h-5 w-5" data-hide-icon fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-14-14zM10 10a1 1 0 100-2 1 1 0 000 2zm0 2a2 2 0 100-4 2 2 0 000 4zm6-4.464V7a2 2 0 00-2-2H9.414a1 1 0 000 2H14v4.414a1 1 0 002 0V9.536z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-slate-500 mt-1">Tu contraseña será verificada de forma segura.</p>
            </div>

            <!-- Motivo Field (Only for rechazo) -->
            @if($tipo === 'rechazo')
            <div>
                <label for="motivo-{{ $folio }}" class="block text-sm font-semibold text-slate-900 mb-2">
                    Motivo del Rechazo <span class="text-red-600">*</span>
                </label>
                <textarea id="motivo-{{ $folio }}" 
                          name="motivo" 
                          required 
                          minlength="10"
                          class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                          placeholder="Explica el motivo del rechazo (mínimo 10 caracteres)"
                          rows="3"></textarea>
                <p class="text-xs text-slate-500 mt-1">El beneficiario recibirá esta justificación.</p>
            </div>
            @endif

            <!-- Warning Box -->
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-amber-800">
                        @if($tipo === 'aprobacion')
                            Una vez aprobada, <strong>no podrá revertirse</strong>. Verifica que todo esté correcto.
                        @else
                            El beneficiario será notificado inmediatamente del rechazo.
                        @endif
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-4 border-t border-slate-200">
                <button type="button" 
                        onclick="closeFirmaModal('{{ $folio }}')"
                        class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition-colors">
                    Cancelar
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 @if($tipo === 'aprobacion')bg-green-600 hover:bg-green-700 @else bg-red-600 hover:bg-red-700 @endif text-white font-medium rounded-lg transition-colors flex items-center justify-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    @if($tipo === 'aprobacion')
                        Confirmar Aprobación
                    @else
                        Confirmar Rechazo
                    @endif
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePasswordVisibility(btn, inputId) {
    const input = document.getElementById(inputId);
    const showIcon = btn.querySelector('[data-show-icon]');
    const hideIcon = btn.querySelector('[data-hide-icon]');
    
    if (input.type === 'password') {
        input.type = 'text';
        showIcon?.classList.add('hidden');
        hideIcon?.classList.remove('hidden');
    } else {
        input.type = 'password';
        showIcon?.classList.remove('hidden');
        hideIcon?.classList.add('hidden');
    }
}

function openFirmaModal(folio) {
    const modal = document.getElementById(`firma-modal-${folio}`);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeFirmaModal(folio) {
    const modal = document.getElementById(`firma-modal-${folio}`);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id^="firma-modal-"]').forEach(modal => {
            if (!modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        });
    }
});

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.matches('[id^="firma-modal-"]')) {
        e.target.classList.add('hidden');
        document.body.style.overflow = '';
    }
});
</script>

<style>
@keyframes slideDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

#firma-modal-{{ $folio }}:not(.hidden) > div {
    animation: slideDown 0.2s ease-out;
}
</style>
