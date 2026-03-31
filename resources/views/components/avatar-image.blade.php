@props([
    'usuario',
    'size' => 'sm', // sm, md, lg
    'showLabel' => false,
])

@php
    $sizeClasses = match($size) {
        'sm' => 'h-10 w-10',
        'md' => 'h-16 w-16',
        'lg' => 'h-40 w-40',
        default => 'h-10 w-10'
    };
    
    $sizePixels = match($size) {
        'sm' => '40',
        'md' => '64',
        'lg' => '160',
        default => '40'
    };
    
    $fotoUrl = $usuario->getFotoUrl();
    $tienePhoto = strpos($fotoUrl, 'storage/fotos') !== false;
@endphp

<div class="flex flex-col items-center">
    <div class="relative group">
        @if($tienePhoto)
            <!-- Imagen real del usuario -->
            <img 
                src="{{ $fotoUrl }}" 
                alt="Foto de {{ $usuario->email }}" 
                class="{{ $sizeClasses }} rounded-full object-cover border-2 border-gray-300 shadow-md group-hover:shadow-lg transition"
            >
        @else
            <!-- Avatar por defecto con ícono de usuario -->
            <div class="{{ $sizeClasses }} rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center border-2 border-gray-300 shadow-md group-hover:shadow-lg transition">
                <svg 
                    class="text-white" 
                    style="width: {{ $sizePixels }}%; height: {{ $sizePixels }}%;"
                    fill="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </div>
            
            <!-- Tooltip informativo -->
            <div class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white px-3 py-2 rounded-lg text-xs whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
                <div class="font-semibold">Usuario sin foto</div>
                <div class="text-gray-300 text-xs">Foto de usuario por defecto</div>
                <div class="w-2 h-2 bg-gray-800 absolute top-full left-1/2 -translate-x-1/2"></div>
            </div>
        @endif
    </div>
    
    @if($showLabel && !$tienePhoto)
        <p class="mt-2 text-sm text-gray-600 text-center">Foto de usuario</p>
    @endif
</div>
