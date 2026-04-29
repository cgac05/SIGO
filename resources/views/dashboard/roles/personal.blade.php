<!-- Menú para Personal (Administrativo) -->
<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-8 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">Panel Administrativo</h2>
                <p class="mt-2 text-indigo-100 italic">Gestiona solicitudes, verifica documentos y administra el sistema</p>
            </div>
            <div class="hidden lg:block">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>



    <!-- Main Administration Sections Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- 1. Verificación de Solicitudes -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:border-indigo-500 transition-all duration-300">
            <div class="h-12 w-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="font-bold text-slate-800 text-lg">Verificación de Solicitudes</h3>
            <p class="text-sm text-slate-500 mt-2">Revisa y verifica documentos de solicitudes. Valida cumplimiento de requisitos.</p>
            <a href="{{ route('admin.solicitudes.index') }}" class="mt-4 inline-block w-full text-center py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 shadow-sm transition-colors">
                Acceder
            </a>
        </div>

        <!-- 2. Gestión de Apoyos -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:border-indigo-500 transition-all duration-300">
            <div class="h-12 w-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
            </div>
            <h3 class="font-bold text-slate-800 text-lg">Gestión de Apoyos</h3>
            <p class="text-sm text-slate-500 mt-2">Administra los apoyos disponibles, tipos de documentos y configuraciones.</p>
            <a href="{{ route('apoyos.index') }}" class="mt-4 inline-block w-full text-center py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 shadow-sm transition-colors">
                Acceder
            </a>
        </div>

        <!-- 3. Gestión de Personal -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:border-indigo-500 transition-all duration-300">
            <div class="h-12 w-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 12H9m6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="font-bold text-slate-800 text-lg">Gestión de Personal</h3>
            <p class="text-sm text-slate-500 mt-2">Administra usuarios y asignación de roles en el sistema.</p>
            <a href="{{ route('personal.index') }}" class="mt-4 inline-block w-full text-center py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 shadow-sm transition-colors">
                Acceder
            </a>
        </div>

        <!-- 4. Configuración de Calendario -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:border-indigo-500 transition-all duration-300">
            <div class="h-12 w-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <h3 class="font-bold text-slate-800 text-lg">Configuración de Calendario</h3>
            <p class="text-sm text-slate-500 mt-2">Sincroniza eventos con Google Calendar y gestiona hitos del programa.</p>
            <a href="{{ route('admin.calendario.config') }}" class="mt-4 inline-block w-full text-center py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 shadow-sm transition-colors">
                Acceder
            </a>
        </div>

        <!-- 5. Gestión de Padrón -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:border-indigo-500 transition-all duration-300">
            <div class="h-12 w-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="font-bold text-slate-800 text-lg">Gestión de Padrón</h3>
            <p class="text-sm text-slate-500 mt-2">Consulta y exporta padrones de beneficiarios por programa.</p>
            <a href="{{ route('admin.padron.index') }}" class="mt-4 inline-block w-full text-center py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 shadow-sm transition-colors">
                Acceder
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
