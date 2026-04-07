<!-- Derechos ARCO - LGPDP -->
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            ⚖️ Derechos ARCO
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Según la LGPDP (Ley General de Protección de Datos Personales), tienes derecho a acceder, rectificar, cancelar u oponertefrente a tus datos personales.
        </p>
    </header>

    <div class="mt-6 space-y-4">
        <!-- Acceso: Descargar mis datos -->
        <div class="border border-gray-200 rounded-lg p-4 hover:bg-blue-50 transition">
            <div class="flex items-start gap-4">
                <div class="text-2xl">📥</div>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900">Acceso (A)</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Descarga una copia de todos tus datos personales en formato JSON o CSV.
                    </p>
                    <form action="{{ route('profile.arco.download') }}" method="POST" class="inline mt-3">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                            📦 Descargar Mis Datos
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rectificación: Editar información -->
        <div class="border border-gray-200 rounded-lg p-4 hover:bg-green-50 transition">
            <div class="flex items-start gap-4">
                <div class="text-2xl">✏️</div>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900">Rectificación (R)</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Corrige o actualiza información personal incorrecta o incompleta.
                    </p>
                    <button 
                        type="button" 
                        onclick="document.getElementById('rectification-form').scrollIntoView({ behavior: 'smooth' })"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 mt-3"
                    >
                        ✏️ Editar Mi Información
                    </button>
                </div>
            </div>
        </div>

        <!-- Cancelación: Solicitar eliminación -->
        <div class="border border-gray-200 rounded-lg p-4 hover:bg-orange-50 transition">
            <div class="flex items-start gap-4">
                <div class="text-2xl">🗑️</div>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900">Cancelación (C)</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Solicita la eliminación de tu cuenta y todos tus datos personales.
                    </p>
                    <p class="text-xs text-orange-700 mt-2 font-medium">
                        ⚠️ Esta acción no se puede deshacer. Se eliminarán todos tus datos después de un período de gracia de 30 días.
                    </p>
                    <form action="{{ route('profile.arco.cancel') }}" method="POST" class="inline mt-3">
                        @csrf
                        <button 
                            type="submit" 
                            onclick="return confirm('¿Solicitar eliminación? Tu cuenta será desactivada por 30 días antes de eliminar definitivamente. ¿Continuar?')"
                            class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700"
                        >
                            🗑️ Solicitar Eliminación
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Oposición: Opt-out de comunicaciones -->
        <div class="border border-gray-200 rounded-lg p-4 hover:bg-purple-50 transition">
            <div class="flex items-start gap-4">
                <div class="text-2xl">🚫</div>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900">Oposición (O)</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Controla qué tipos de notificaciones y comunicaciones deseas recibir.
                    </p>
                    <button 
                        type="button" 
                        onclick="showNotificationPreferences()"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 mt-3"
                    >
                        🔕 Preferencias de Notificaciones
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Información Legal -->
    <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 class="font-medium text-gray-900 mb-2">📋 Información Importante</h4>
        <ul class="text-sm text-gray-700 space-y-1">
            <li>✓ Tus derechos ARCO están protegidos por la LGPDP mexicana</li>
            <li>✓ Procesaremos tu solicitud en un plazo máximo de 20 días hábiles</li>
            <li>✓ Puedes ejercer tus derechos sin costo alguno</li>
            <li>✓ Para dudas, contacta a: <span class="font-mono text-xs bg-white px-2 py-1 rounded">privacidad@injuve.gob.mx</span></li>
        </ul>
    </div>
</section>

<!-- Modal de Preferencias de Notificaciones -->
<div id="notificationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="font-medium text-gray-900 mb-4">Preferencias de Notificaciones</h3>
        
        <form action="{{ route('profile.update-notification-preferences') }}" method="POST" class="space-y-4">
            @csrf
            
            <label class="flex items-center gap-3">
                <input type="checkbox" name="notif_email_news" {{ $user->notif_email_news ? 'checked' : '' }} class="rounded">
                <span class="text-sm text-gray-700">📰 Recibir noticias y actualizaciones</span>
            </label>

            <label class="flex items-center gap-3">
                <input type="checkbox" name="notif_email_apoyos" {{ $user->notif_email_apoyos ? 'checked' : '' }} class="rounded">
                <span class="text-sm text-gray-700">🎯 Notificaciones sobre nuevos apoyos</span>
            </label>

            <label class="flex items-center gap-3">
                <input type="checkbox" name="notif_email_status" {{ $user->notif_email_status ? 'checked' : '' }} class="rounded">
                <span class="text-sm text-gray-700">✅ Cambios de estado en mis solicitudes</span>
            </label>

            <label class="flex items-center gap-3">
                <input type="checkbox" name="notif_email_marketing" {{ $user->notif_email_marketing ? 'checked' : '' }} class="rounded">
                <span class="text-sm text-gray-700">📣 Promociones y ofertas especiales</span>
            </label>

            <div class="flex gap-2 pt-4 border-t">
                <button type="submit" class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 text-sm font-medium">
                    💾 Guardar
                </button>
                <button type="button" onclick="document.getElementById('notificationModal').classList.add('hidden')" class="flex-1 bg-gray-300 text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-400 text-sm font-medium">
                    ✕ Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showNotificationPreferences() {
    document.getElementById('notificationModal').classList.remove('hidden');
}
</script>
