@php
    $isBeneficiario = $user && $user->isBeneficiario();
    $isAdmin = $user && $user->personal && (int) $user->personal->fk_rol === 1;
    $isDirector = $user && $user->personal && (int) $user->personal->fk_rol === 2;
    $canEdit = $isAdmin || $isDirector;
@endphp

<!DOCTYPE html>
<html>
<head>
    <title>Apoyos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <h1 class="text-3xl font-bold p-10">Apoyos - Sin Componente</h1>
    
    <div class="p-10">
        <p class="mb-5">
            <strong>User:</strong> {{ $user->email ?? 'No user' }} |
            <strong>Beneficiario:</strong> {{ $isBeneficiario ? 'Sí' : 'No' }} |
            <strong>Apoyos Count:</strong> <span class="text-2xl font-bold text-red-600">{{ count($apoyos) }}</span>
        </p>

        @if(count($apoyos) > 0)
            <p class="mb-10 p-3 bg-green-100 text-green-800">✓ Datos cargados correctamente</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($apoyos as $apoyo)
                    <div class="border rounded p-4 bg-white shadow">
                        <h3 class="font-bold text-lg mb-2">{{ $apoyo->nombre_apoyo }}</h3>
                        <p><strong>ID:</strong> {{ $apoyo->id_apoyo }}</p>
                        <p><strong>Tipo:</strong> {{ $apoyo->tipo_apoyo }}</p>
                        <p><strong>Monto:</strong> ${{ number_format($apoyo->monto_maximo ?? 0, 2) }}</p>
                        <p><strong>Vigencia:</strong> {{ $apoyo->fecha_inicio ?? 'N/A' }} al {{ $apoyo->fecha_fin ?? 'N/A' }}</p>
                        <p><strong>Activo:</strong> {{ $apoyo->activo ? 'Sí' : 'No' }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="p-3 bg-red-100 text-red-800">✗ No hay apoyos disponibles</p>
            <pre class="bg-gray-100 p-4 mt-5 overflow-auto">{{ json_encode($apoyos->toArray(), JSON_PRETTY_PRINT) }}</pre>
        @endif
    </div>
</body>
</html>
