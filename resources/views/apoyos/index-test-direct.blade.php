<!DOCTYPE html>
<html>
<head>
    <title>Apoyos Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="p-10">
        <h1 class="text-3xl font-bold mb-5">Apoyos Test Direct (sin componente)</h1>
        
        <p class="mb-3">User: {{ $user->email ?? 'No user' }}</p>
        <p class="mb-5">Apoyos count: <span class="font-bold text-2xl">{{ count($apoyos) }}</span></p>
        
        @if(count($apoyos) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($apoyos as $apoyo)
                    <div class="border p-4 rounded">
                        <h3 class="font-bold text-lg">{{ $apoyo->nombre_apoyo }}</h3>
                        <p>ID: {{ $apoyo->id_apoyo }}</p>
                        <p>Vigencia: {{ $apoyo->fecha_inicio }} al {{ $apoyo->fecha_fin }}</p>
                        <p>Monto: ${{ number_format($apoyo->monto_maximo ?? 0, 2) }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-red-500 font-bold">No apoyos found!</p>
        @endif
    </div>
</body>
</html>
