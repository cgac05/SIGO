<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Padrón de Usuarios - {{ config('app.name', 'SIGO') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Padrón de Usuarios</h2>
            </div>
        </header>

        <main>
            <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Padrón de Usuarios</h1>
                <p class="mt-2 text-sm text-gray-600">Gestión centralizada de beneficiarios y personal administrativo</p>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <!-- Total General -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-gray-500 text-sm font-medium">Total de Usuarios</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $estadisticas['total_general'] }}</p>
                        </div>
                        <svg class="w-12 h-12 text-blue-200" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Beneficiarios -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-gray-500 text-sm font-medium">Beneficiarios</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $estadisticas['total_beneficiarios'] }}</p>
                        </div>
                        <svg class="w-12 h-12 text-green-200" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v2h8v-2zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-2a4 4 0 00-8 0v2a2 2 0 002 2h4a2 2 0 002-2z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Personal -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-gray-500 text-sm font-medium">Personal</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $estadisticas['total_personal'] }}</p>
                        </div>
                        <svg class="w-12 h-12 text-purple-200" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM15.657 14.243a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM11 17a1 1 0 102 0v-1a1 1 0 10-2 0v1zM5.757 15.657a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM2 10a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.757 4.343A1 1 0 004.343 5.757l.707.707a1 1 0 001.414-1.414l-.707-.707z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Estado -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-gray-500 text-sm font-medium">Inactivos</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $estadisticas['total_inactivos'] }}</p>
                        </div>
                        <span class="inline-flex items-center justify-center h-12 w-12 rounded-md bg-red-500 bg-opacity-0">
                            <svg class="h-6 w-6 text-red-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Filtros + Tabla -->
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Sidebar Filtros -->
                <div class="md:w-72">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L13 11.414V19a1 1 0 01-1.447.894l-4-2A1 1 0 017 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"></path>
                            </svg>
                            Filtros
                        </h3>

                        <form method="GET" action="{{ route('padron.index') }}" class="space-y-6">
                            <!-- Búsqueda -->
                            <div>
                                <label for="busqueda" class="block text-sm font-semibold text-gray-700 mb-2">Buscar</label>
                                <input
                                    type="text"
                                    id="busqueda"
                                    name="busqueda"
                                    value="{{ $busqueda }}"
                                    placeholder="Nombre, Email, ID..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                >
                            </div>

                            <!-- Tipo de Usuario -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Tipo de Usuario</label>
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <input type="radio" id="tipo-todos" name="tipo" value="todos" {{ $tipo === 'todos' ? 'checked' : '' }} class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                        <label for="tipo-todos" class="ml-2 text-sm text-gray-700 cursor-pointer">Todos</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="tipo-beneficiarios" name="tipo" value="beneficiarios" {{ $tipo === 'beneficiarios' ? 'checked' : '' }} class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                        <label for="tipo-beneficiarios" class="ml-2 text-sm text-gray-700 cursor-pointer">Beneficiarios</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="tipo-personal" name="tipo" value="personal" {{ $tipo === 'personal' ? 'checked' : '' }} class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                        <label for="tipo-personal" class="ml-2 text-sm text-gray-700 cursor-pointer">Personal Administrativo</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Rol (solo si es personal) -->
                            @if($tipo === 'personal' || $tipo === 'todos')
                            <div>
                                <label for="rol" class="block text-sm font-semibold text-gray-700 mb-2">Rol</label>
                                <select name="rol" id="rol" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="">- Todos los roles -</option>
                                    @foreach($roles as $r)
                                    <option value="{{ $r->id_rol }}" {{ $rol == $r->id_rol ? 'selected' : '' }}>
                                        {{ $r->nombre_rol }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <!-- Estado -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Estado</label>
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <input type="radio" id="estado-activo" name="estado" value="activo" {{ $estado === 'activo' ? 'checked' : '' }} class="h-4 w-4 text-green-600 border-gray-300 rounded">
                                        <label for="estado-activo" class="ml-2 text-sm text-gray-700 cursor-pointer">
                                            <span class="inline-block h-2 w-2 bg-green-500 rounded-full mr-1"></span>Activos
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="estado-inactivo" name="estado" value="inactivo" {{ $estado === 'inactivo' ? 'checked' : '' }} class="h-4 w-4 text-red-600 border-gray-300 rounded">
                                        <label for="estado-inactivo" class="ml-2 text-sm text-gray-700 cursor-pointer">
                                            <span class="inline-block h-2 w-2 bg-red-500 rounded-full mr-1"></span>Inactivos
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Acciones -->
                            <div class="flex gap-2 pt-4 border-t">
                                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                                    Filtrar
                                </button>
                                <a href="{{ route('padron.index') }}" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition text-center">
                                    Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de Usuarios -->
                <div class="flex-1">
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <!-- Controles superiores -->
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600">
                                    Mostrando <span class="font-semibold">{{ $usuarios->count() }}</span> de
                                    <span class="font-semibold">{{ $usuarios->total() }}</span> usuarios
                                </p>
                            </div>
                            <a href="{{ route('padron.exportar', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                                Exportar CSV
                            </a>
                        </div>

                        <!-- Tabla -->
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Foto</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Nombre</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Tipo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Identificación</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Estado</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse($usuarios as $usuario)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <x-avatar-image :usuario="$usuario" size="sm" />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                @if($usuario->isBeneficiario())
                                                    {{ $usuario->beneficiario->nombre }} {{ $usuario->beneficiario->apellido_paterno }}
                                                @else
                                                    {{ $usuario->personal->nombre }} {{ $usuario->personal->apellido_paterno }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-600">{{ $usuario->email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($usuario->isBeneficiario())
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Beneficiario
                                                </span>
                                            @elseif($usuario->isPersonal())
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                    {{ ucfirst($usuario->tipo_usuario) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            @if($usuario->isBeneficiario())
                                                {{ $usuario->beneficiario->curp }}
                                            @else
                                                {{ $usuario->personal->numero_empleado }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($usuario->activo)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <span class="inline-block h-2 w-2 bg-green-500 rounded-full mr-2"></span>Activo
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <span class="inline-block h-2 w-2 bg-red-500 rounded-full mr-2"></span>Inactivo
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex gap-2">
                                                <a href="{{ route('padron.show', $usuario->id_usuario) }}" class="text-blue-600 hover:text-blue-900 transition" title="Ver detalles">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </a>
                                                <button class="text-gray-600 hover:text-gray-900 transition" title="Más opciones">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <p>No se encontraron usuarios con los filtros seleccionados</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            {{ $usuarios->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
