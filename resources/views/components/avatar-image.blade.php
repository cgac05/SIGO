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
    
    $fotoUrl = $usuario->getFotoUrl();
    $avatarUrl = $usuario->avatar_url;
    $avatarFallbackUrl = $usuario->avatar_placeholder_url;
    $tienePhoto = filled($fotoUrl);
    $avatarLabel = $usuario->display_name ?: $usuario->email ?: 'Usuario';
@endphp

<div class="flex flex-col items-center">
    <div class="relative group">
        <img
            src="{{ $avatarUrl }}"
            alt="Foto de {{ $avatarLabel }}"
            class="{{ $sizeClasses }} rounded-full object-cover border-2 border-gray-300 shadow-md group-hover:shadow-lg transition"
            onerror="this.onerror=null;this.src='{{ $avatarFallbackUrl }}';"
        >

        @if(!$tienePhoto)
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
