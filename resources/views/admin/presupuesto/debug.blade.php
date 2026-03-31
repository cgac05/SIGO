@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5>🔍 DEBUG: Información de Acceso</h5>
        </div>
        <div class="card-body">
            <h6>Usuario Autenticado:</h6>
            <ul>
                <li><strong>ID:</strong> {{ auth()->user()->id_usuario }}</li>
                <li><strong>Email:</strong> {{ auth()->user()->email }}</li>
                <li><strong>Tipo Usuario:</strong> {{ auth()->user()->tipo_usuario }}</li>
                <li><strong>isPersonal():</strong> {{ auth()->user()->isPersonal() ? '✅ SÍ' : '❌ NO' }}</li>
            </ul>

            <hr>

            <h6>Información de Personal:</h6>
            @if (auth()->user()->personal)
                <ul>
                    <li><strong>ID Personal:</strong> {{ auth()->user()->personal->id_personal ?? 'N/A' }}</li>
                    <li><strong>Número Empleado:</strong> {{ auth()->user()->personal->numero_empleado ?? 'N/A' }}</li>
                    <li><strong>Nombre:</strong> {{ auth()->user()->personal->nombre ?? 'N/A' }}</li>
                    <li><strong>fk_rol:</strong> {{ auth()->user()->personal->fk_rol ?? 'NULL ❌' }}</li>
                    <li>
                        <strong>Rol Nombre:</strong> 
                        @php
                            $roles = ['1' => 'Administrativo', '2' => 'Directivo', '3' => 'Finanzas'];
                        @endphp
                        {{ $roles[auth()->user()->personal->fk_rol ?? 'unknown'] ?? 'DESCONOCIDO ❌' }}
                    </li>
                </ul>
            @else
                <div class="alert alert-danger">
                    ❌ El usuario NO tiene registro en la tabla Personal
                </div>
            @endif

            <hr>

            <h6>Verificación de Acceso:</h6>
            @php
                $userRole = auth()->user()->personal?->fk_rol;
                $rolesRequeridos = [2]; // role:2 requerido
                $tieneAcceso = $userRole !== null && in_array($userRole, $rolesRequeridos);
            @endphp
            
            <ul>
                <li><strong>Rol del usuario:</strong> {{ $userRole ?? 'NULL' }}</li>
                <li><strong>Roles requeridos para /admin/presupuesto:</strong> {{ implode(', ', $rolesRequeridos) }}</li>
                <li><strong>¿Tiene acceso?:</strong> 
                    @if ($tieneAcceso)
                        ✅ SÍ
                    @else
                        ❌ NO
                    @endif
                </li>
            </ul>

            <hr>

            <div class="alert alert-info">
                <strong>Próximos pasos:</strong>
                <ol>
                    <li>Si muestra "❌ El usuario NO tiene registro en la tabla Personal", necesita crear un registro Personal para este usuario</li>
                    <li>Si fk_rol es NULL, asigne rol 2 en tabla Personal</li>
                    <li>Una vez configurado, vuelva a intentar acceder al dashboard</li>
                </ol>
            </div>

            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>
@endsection
