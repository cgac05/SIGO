@php
    $user = auth()->user();
@endphp

<!DOCTYPE html>
<html>
<head>
    <title>Apoyos - Test Simple</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-blue-600 mb-6">✓ PÁGINA DE APOYOS</h1>
        
        <div class="bg-white p-6 rounded shadow mb-6">
            <p class="text-lg">Usuario: <strong>{{ $user->name ?? 'No autenticado' }}</strong></p>
            <p class="text-lg">Total apoyos en data: <strong>{{ count($apoyos ?? []) }}</strong></p>
        </div>

        @if(count($apoyos ?? []) > 0)
            <div class="grid grid-cols-3 gap-4">
                @foreach($apoyos as $a)
                    <div class="bg-white p-4 rounded shadow">
                        <h3 class="font-bold">{{ $a->nombre_apoyo }}</h3>
                        <p class="text-sm text-gray-600">{{ $a->tipo_apoyo }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-yellow-100 border-2 border-yellow-400 p-6 rounded text-center">
                <p class="text-xl font-bold text-yellow-800">⚠ NO HAY APOYOS</p>
                <p class="text-yellow-700">$apoyos está vacío o no existe</p>
            </div>
        @endif
    </div>
</body>
</html>
