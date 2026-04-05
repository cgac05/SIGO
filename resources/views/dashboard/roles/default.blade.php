<!-- Menú Default para usuarios sin rol específico -->
<div class="space-y-6">
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
        <h3 class="text-lg font-bold text-yellow-900">⚠️ Acceso Limitado</h3>
        <p class="text-yellow-800 mt-2">
            Tu cuenta no tiene un rol definido. Contacta al administrador para asignar los permisos necesarios.
        </p>
    </div>

    <!-- Basic Options -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h4 class="text-lg font-bold text-slate-900">👤 Mi Perfil</h4>
            <p class="text-sm text-slate-600 mt-2">Visualiza y edita tu información personal</p>
            <a href="{{ route('profile.edit') }}" class="mt-4 block w-full px-4 py-2 text-center bg-blue-600 text-white rounded hover:bg-blue-700 transition font-medium">
                Acceder
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h4 class="text-lg font-bold text-slate-900">🔔 Notificaciones</h4>
            <p class="text-sm text-slate-600 mt-2">Revisa tus mensajes y notificaciones</p>
            <a href="{{ route('beneficiario.notificaciones.inbox') }}" class="mt-4 block w-full px-4 py-2 text-center bg-amber-600 text-white rounded hover:bg-amber-700 transition font-medium">
                Acceder
            </a>
        </div>
    </div>

    <!-- Portal Público -->
    <div class="bg-white rounded-lg shadow p-6">
        <h4 class="text-lg font-bold text-slate-900">🔓 Portal Público</h4>
        <p class="text-sm text-slate-600 mt-2">Acceso a verificación pública de solicitudes</p>
        <a href="{{ route('solicitudes.publico.validar') }}" class="mt-4 block w-full px-4 py-2 text-center bg-green-600 text-white rounded hover:bg-green-700 transition font-medium">
            Ir a Portal Público
        </a>
    </div>

    <!-- Contact Support -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h4 class="text-lg font-bold text-blue-900">📞 Soporte Técnico</h4>
        <p class="text-blue-800 mt-2">
            Si crees que este es un error o necesitas ayuda:
        </p>
        <div class="mt-4 space-y-2">
            <p class="text-sm text-blue-900">
                <strong>Email:</strong> <a href="mailto:soporte@ejemplo.com" class="underline hover:text-blue-700">soporte@ejemplo.com</a>
            </p>
            <p class="text-sm text-blue-900">
                <strong>Teléfono:</strong> +1 (555) 000-0000
            </p>
        </div>
    </div>
</div>
