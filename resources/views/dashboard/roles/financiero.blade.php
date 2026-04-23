<div class="space-y-6">
    <div class="bg-gradient-to-r from-emerald-600 to-green-700 rounded-2xl p-8 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">Panel de Recursos Financieros</h2>
                <p class="mt-2 text-green-100 italic">Gestión de dispersiones, validación de pagos y cierres presupuestales.</p>
            </div>
            <div class="hidden lg:block">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zM12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z" />
                </svg>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:border-emerald-500 transition-all duration-300">
            <div class="h-12 w-12 bg-emerald-100 rounded-lg flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="font-bold text-slate-800 text-lg">Proceso de Cierre</h3>
            <p class="text-sm text-slate-500 mt-2">Valida las solicitudes que ya tienen firma para proceder al pago.</p>
            <a href="{{ route('finanzas.panel') }}" class="mt-4 inline-block w-full text-center py-2 bg-emerald-600 text-white rounded-lg font-semibold hover:bg-emerald-700 shadow-sm transition-colors">
                Entrar al Módulo
            </a>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:border-emerald-500 transition-all duration-300">
            <div class="h-12 w-12 bg-slate-100 rounded-lg flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="font-bold text-slate-800 text-lg">Reportes de Egresos</h3>
            <p class="text-sm text-slate-500 mt-2">Consulta el histórico de apoyos entregados y estados de cuenta.</p>
            <button class="mt-4 inline-block w-full text-center py-2 border border-emerald-600 text-emerald-600 rounded-lg font-semibold hover:bg-emerald-50 transition-colors">
                Ver Historial
            </button>
        </div>

    </div>
</div>