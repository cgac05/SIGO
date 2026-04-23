<!-- Menú para Beneficiarios -->
<div class="space-y-6">
    <!-- Perfil Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Mi Perfil -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">👤 Mi Perfil</h3>
                    <p class="text-sm text-slate-600 mt-1">Gestiona tu información personal</p>
                </div>
                <span class="text-3xl">👤</span>
            </div>
            <div class="mt-4 space-y-2">
                <a href="{{ route('profile.edit') }}" class="block w-full px-4 py-2 text-center bg-blue-50 text-blue-700 rounded hover:bg-blue-100 transition text-sm font-medium">
                    Ver / Editar Perfil
                </a>
                <a href="{{ route('registro.completar-perfil.show') }}" class="block w-full px-4 py-2 text-center bg-blue-50 text-blue-700 rounded hover:bg-blue-100 transition text-sm font-medium">
                    Completar Información
                </a>
            </div>
        </div>

        <!-- Mis Notificaciones -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-amber-500">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">🔔 Mis Notificaciones</h3>
                    <p class="text-sm text-slate-600 mt-1">Revisa tus mensajes importantes</p>
                </div>
                <span class="text-3xl">🔔</span>
            </div>
            <div class="mt-4">
                <a href="{{ route('beneficiario.notificaciones.inbox') }}" class="block w-full px-4 py-2 text-center bg-amber-50 text-amber-700 rounded hover:bg-amber-100 transition text-sm font-medium">
                    Ver Notificaciones
                </a>
            </div>
        </div>
    </div>

    <!-- Solicitudes Section -->
    <div class="space-y-3">
        <h2 class="text-xl font-bold text-slate-900">📋 Mis Solicitudes de Apoyo</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Registrar Nueva Solicitud -->
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg shadow p-6 border border-green-200 hover:shadow-lg transition">
                <h4 class="text-lg font-bold text-green-900">➕ Nueva Solicitud</h4>
                <p class="text-sm text-green-700 mt-2">Inicia el proceso de solicitud de apoyo</p>
                <a href="{{ route('solicitudes.registrar') }}" class="mt-4 block w-full px-4 py-2 text-center bg-green-600 text-white rounded hover:bg-green-700 transition font-medium">
                    Registrar Solicitud
                </a>
            </div>

            <!-- Ver Mis Solicitudes -->
            <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-lg shadow p-6 border border-blue-200 hover:shadow-lg transition">
                <h4 class="text-lg font-bold text-blue-900">📑 Mis Solicitudes</h4>
                <p class="text-sm text-blue-700 mt-2">Consulta el estado y detalle completo de cada una</p>
                <a href="{{ route('solicitudes.historial') }}" class="mt-4 block w-full px-4 py-2 text-center bg-blue-600 text-white rounded hover:bg-blue-700 transition font-medium">
                    Ver historial
                </a>
            </div>
        </div>
    </div>

    <!-- Documentos Section -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-slate-900 flex items-center">
            <span class="text-2xl mr-2">📄</span>
            Documentos Requeridos
        </h3>
        <p class="text-sm text-slate-600 mt-2">Carga y gestiona tus documentos de apoyo</p>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
            <a href="{{ route('apoyos.index') }}" class="px-4 py-2 text-center bg-slate-100 text-slate-700 rounded hover:bg-slate-200 transition text-sm font-medium">
                📤 Cargar Documentos
            </a>
            <a href="#" class="px-4 py-2 text-center bg-slate-100 text-slate-700 rounded hover:bg-slate-200 transition text-sm font-medium">
                ✅ Ver Validaciones
            </a>
        </div>
    </div>

    <!-- Acceso Rápido Section -->
    <div class="bg-slate-50 rounded-lg p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-4">⚡ Acceso Rápido</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <a href="{{ route('solicitudes.publico.validar') }}" class="p-3 bg-white rounded border border-slate-200 hover:border-slate-400 hover:shadow transition text-center">
                <span class="text-2xl block mb-1">🔐</span>
                <span class="text-xs font-medium text-slate-700">Portal Público CUV</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="p-3 bg-white rounded border border-slate-200 hover:border-slate-400 hover:shadow transition text-center">
                <span class="text-2xl block mb-1">🔑</span>
                <span class="text-xs font-medium text-slate-700">Cambiar Contraseña</span>
            </a>
            <a href="{{ route('beneficiario.notificaciones.inbox') }}" class="p-3 bg-white rounded border border-slate-200 hover:border-slate-400 hover:shadow transition text-center">
                <span class="text-2xl block mb-1">📬</span>
                <span class="text-xs font-medium text-slate-700">Mensajes Nuevos</span>
            </a>
        </div>
    </div>

    <!-- Info Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-sm text-blue-900">
            <strong>💡 Tip:</strong> Mantén actualizado tu perfil y carga todos los documentos requeridos para acelerar el proceso de tus solicitudes.
        </p>
    </div>
</div>
