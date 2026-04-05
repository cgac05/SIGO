<!-- Menú para Personal (Administrativo y Directivos) -->
<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg shadow p-8 text-white">
        <h2 class="text-2xl font-bold">Panel de Administración</h2>
        <p class="text-indigo-100 mt-2">Gestiona solicitudes, verifica documentos y administra el sistema</p>
    </div>

    <!-- Quick Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-sm text-slate-600">Solicitudes Pendientes</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">--</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-sm text-slate-600">Validadas Hoy</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">--</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-amber-500">
            <p class="text-sm text-slate-600">Con Observaciones</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">--</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
            <p class="text-sm text-slate-600">Certificadas</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">--</p>
        </div>
    </div>

    <!-- Main Administration Sections -->
    <div class="space-y-4">
        <!-- 1. Gestión de Solicitudes -->
        <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <span class="text-2xl mr-2">📋</span>
                    Gestión de Solicitudes
                </h3>
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-3 gap-3">
                <a href="{{ route('solicitudes.proceso.index') }}" class="p-4 bg-blue-50 rounded hover:bg-blue-100 transition">
                    <span class="text-2xl">⚙️</span>
                    <p class="text-sm font-medium text-blue-900 mt-2">Proceso de Cierre</p>
                </a>
                <a href="#" class="p-4 bg-blue-50 rounded hover:bg-blue-100 transition">
                    <span class="text-2xl">✅</span>
                    <p class="text-sm font-medium text-blue-900 mt-2">Validación Rápida</p>
                </a>
                <a href="#" class="p-4 bg-blue-50 rounded hover:bg-blue-100 transition">
                    <span class="text-2xl">💬</span>
                    <p class="text-sm font-medium text-blue-900 mt-2">Ver Comentarios</p>
                </a>
                <a href="#" class="p-4 bg-blue-50 rounded hover:bg-blue-100 transition">
                    <span class="text-2xl">📊</span>
                    <p class="text-sm font-medium text-blue-900 mt-2">Reportes</p>
                </a>
                <a href="#" class="p-4 bg-blue-50 rounded hover:bg-blue-100 transition">
                    <span class="text-2xl">🔍</span>
                    <p class="text-sm font-medium text-blue-900 mt-2">Búsqueda Avanzada</p>
                </a>
                <a href="#" class="p-4 bg-blue-50 rounded hover:bg-blue-100 transition">
                    <span class="text-2xl">📁</span>
                    <p class="text-sm font-medium text-blue-900 mt-2">Archivo</p>
                </a>
            </div>
        </div>

        <!-- 2. Verificación y Certificación -->
        <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <span class="text-2xl mr-2">🔐</span>
                    Verificación y Certificación Digital
                </h3>
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-3">
                <a href="{{ route('certificacion.digital.dashboard') ?? '#' }}" class="p-4 bg-green-50 rounded hover:bg-green-100 transition">
                    <span class="text-2xl">🎫</span>
                    <p class="text-sm font-medium text-green-900 mt-2">Certificación Digital</p>
                </a>
                <a href="{{ route('certificacion.verificacion.dashboard') ?? '#' }}" class="p-4 bg-green-50 rounded hover:bg-green-100 transition">
                    <span class="text-2xl">✔️</span>
                    <p class="text-sm font-medium text-green-900 mt-2">Verificación</p>
                </a>
                <a href="{{ route('certificacion.archivado.dashboard') ?? '#' }}" class="p-4 bg-green-50 rounded hover:bg-green-100 transition">
                    <span class="text-2xl">📦</span>
                    <p class="text-sm font-medium text-green-900 mt-2">Archivado</p>
                </a>
                <a href="{{ route('certificacion.archivado.gestor') ?? '#' }}" class="p-4 bg-green-50 rounded hover:bg-green-100 transition">
                    <span class="text-2xl">💾</span>
                    <p class="text-sm font-medium text-green-900 mt-2">Gestor de Archivos</p>
                </a>
            </div>
        </div>

        <!-- 3. Reportes y Exportación -->
        <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
            <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-6 py-4">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <span class="text-2xl mr-2">📊</span>
                    Reportes y Exportación
                </h3>
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-3 gap-3">
                <a href="#" class="p-4 bg-amber-50 rounded hover:bg-amber-100 transition">
                    <span class="text-2xl">📈</span>
                    <p class="text-sm font-medium text-amber-900 mt-2">Dashboard KPI</p>
                </a>
                <a href="#" class="p-4 bg-amber-50 rounded hover:bg-amber-100 transition">
                    <span class="text-2xl">📋</span>
                    <p class="text-sm font-medium text-amber-900 mt-2">Reportes Mensuales</p>
                </a>
                <a href="#" class="p-4 bg-amber-50 rounded hover:bg-amber-100 transition">
                    <span class="text-2xl">💼</span>
                    <p class="text-sm font-medium text-amber-900 mt-2">Reportes Ejecutivos</p>
                </a>
                <a href="#" class="p-4 bg-amber-50 rounded hover:bg-amber-100 transition">
                    <span class="text-2xl">📊</span>
                    <p class="text-sm font-medium text-amber-900 mt-2">Estadísticas</p>
                </a>
                <a href="#" class="p-4 bg-amber-50 rounded hover:bg-amber-100 transition">
                    <span class="text-2xl">📥</span>
                    <p class="text-sm font-medium text-amber-900 mt-2">Exportar Excel</p>
                </a>
                <a href="#" class="p-4 bg-amber-50 rounded hover:bg-amber-100 transition">
                    <span class="text-2xl">📄</span>
                    <p class="text-sm font-medium text-amber-900 mt-2">Exportar PDF</p>
                </a>
            </div>
        </div>

        <!-- 4. Configuración y Administración -->
        <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
            <div class="bg-gradient-to-r from-slate-600 to-slate-700 px-6 py-4">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <span class="text-2xl mr-2">⚙️</span>
                    Configuración y Administración
                </h3>
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-3 gap-3">
                <a href="{{ route('profile.edit') }}" class="p-4 bg-slate-50 rounded hover:bg-slate-100 transition">
                    <span class="text-2xl">👤</span>
                    <p class="text-sm font-medium text-slate-900 mt-2">Mi Perfil</p>
                </a>
                <a href="#" class="p-4 bg-slate-50 rounded hover:bg-slate-100 transition">
                    <span class="text-2xl">🔒</span>
                    <p class="text-sm font-medium text-slate-900 mt-2">Seguridad</p>
                </a>
                <a href="#" class="p-4 bg-slate-50 rounded hover:bg-slate-100 transition">
                    <span class="text-2xl">🗂️</span>
                    <p class="text-sm font-medium text-slate-900 mt-2">Categorías</p>
                </a>
                <a href="#" class="p-4 bg-slate-50 rounded hover:bg-slate-100 transition">
                    <span class="text-2xl">📋</span>
                    <p class="text-sm font-medium text-slate-900 mt-2">Configuración</p>
                </a>
                <a href="#" class="p-4 bg-slate-50 rounded hover:bg-slate-100 transition">
                    <span class="text-2xl">👥</span>
                    <p class="text-sm font-medium text-slate-900 mt-2">Gestión de Usuarios</p>
                </a>
                <a href="#" class="p-4 bg-slate-50 rounded hover:bg-slate-100 transition">
                    <span class="text-2xl">📊</span>
                    <p class="text-sm font-medium text-slate-900 mt-2">Auditoría</p>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer Info -->
    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
        <p class="text-sm text-slate-900">
            <strong>📞 Centro de Ayuda:</strong> Si necesitas ayuda, contacta al área de soporte técnico.
        </p>
    </div>
</div>
