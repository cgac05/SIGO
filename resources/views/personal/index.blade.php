<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-slate-200">
                
                <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">Padrón de Personal</h2>
                        <p class="text-sm text-slate-500 mt-1">Gestión de usuarios administrativos del sistema SIGO.</p>
                    </div>
                    <a href="{{ route('personal.create') }}" 
                       class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded-xl transition shadow-md shadow-indigo-200 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Nuevo Empleado
                    </a>
                </div>

                <div class="p-8">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-slate-400 text-xs uppercase tracking-wider border-b border-slate-100">
                                <th class="pb-4 px-4">Empleado</th>
                                <th class="pb-4 px-4">Núm. Empleado</th>
                                <th class="pb-4 px-4">Rol</th>
                                <th class="pb-4 px-4 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
    @foreach($empleados as $empleado)
    <tr class="hover:bg-slate-50/50 transition">
        <td class="py-5 px-4">
            <div class="font-bold text-slate-800">{{ $empleado->nombre }} {{ $empleado->apellido_paterno }}</div>
            <div class="text-xs text-slate-400">{{ $empleado->user->email ?? 'Sin correo' }}</div>
        </td>
        <td class="py-5 px-4 font-mono text-sm text-slate-600">{{ $empleado->numero_empleado }}</td>
        <td class="py-5 px-4">
            <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs font-bold uppercase">
                {{ $empleado->role->nombre_rol ?? 'Sin Rol' }}
            </span>
        </td>
        <td class="py-5 px-4 text-center">
            <div class="flex justify-center gap-3">
                <a href="{{ route('personal.edit', $empleado->numero_empleado) }}" class="text-slate-400 hover:text-blue-600 transition" title="Editar">
                    {{-- Icono Editar --}}
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </a>
                <form action="{{ route('personal.destroy', $empleado->numero_empleado) }}" method="POST" class="inline" onsubmit="return confirm('¿Está seguro de eliminar a este empleado?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-slate-400 hover:text-red-600 transition" title="Eliminar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </form>
            </div>
        </td>
    </tr>
    @endforeach
</tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>