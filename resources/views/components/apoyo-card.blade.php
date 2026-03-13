@props([
    'apoyo',
    'userRole' => 'beneficiario',
    'showEditDelete' => false,
])

<div class="apoyo-card" @click="abrirModal({{ json_encode($apoyo) }})">

    <div class="card-img-wrap">
        @if($apoyo->foto_ruta ?? false)
            <img src="{{ asset($apoyo->foto_ruta) }}" alt="{{ $apoyo->nombre_apoyo }}" />
        @else
            <div class="card-img-placeholder">
                <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M3.75 3h16.5M4.5 3v18m15-18v18"/>
                </svg>
                <span class="text-xs font-semibold opacity-60">Sin imagen</span>
            </div>
        @endif

        <span class="tipo-badge"
              :class="apoyo.tipo_apoyo === 'Económico' ? 'economico' : 'especie'"
              style="position: absolute; top: 12px; right: 12px;">
            {{ $apoyo->tipo_apoyo }}
        </span>

        @if($showEditDelete)
            <div class="absolute top-2 left-2 flex gap-2" @click.stop>
                <button type="button"
                        class="w-8 h-8 rounded-full bg-blue-500 hover:bg-blue-600 text-white flex items-center justify-center transition"
                        title="Editar apoyo"
                        @click="abrirEditar({{ json_encode($apoyo) }})">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button type="button"
                        class="w-8 h-8 rounded-full bg-red-500 hover:bg-red-600 text-white flex items-center justify-center transition"
                        title="Eliminar apoyo"
                        @click="abrirEliminar({{ json_encode($apoyo) }})">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        @endif
    </div>

    <div class="card-body">
        <div class="card-title">{{ $apoyo->nombre_apoyo }}</div>

        @if($userRole === 'beneficiario')
            <div class="card-meta">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25"/>
                </svg>
                <span>Vigente hasta {{ \Carbon\Carbon::parse($apoyo->fechafin ?? $apoyo->fecha_fin)->format('d/m/Y') }}</span>
            </div>

            @if($apoyo->tipo_apoyo === 'Económico' && ($apoyo->monto_maximo ?? 0) > 0)
                <div class="card-meta" style="color: #92400e">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33"/>
                    </svg>
                    <span>Hasta ${{ number_format($apoyo->monto_maximo, 2) }}</span>
                </div>
            @endif

            <div class="btn-ver">Ver detalles y solicitar →</div>
        @else
            {{-- Vista para administrativo --}}
            <div class="text-sm text-gray-500 mb-2">
                Monto máximo: <strong>${{ number_format($apoyo->monto_maximo, 2) }}</strong>
            </div>
            <div class="text-xs text-gray-400">
                Estado: <strong>{{ $apoyo->activo ? 'Activo' : 'Inactivo' }}</strong>
            </div>
            <div class="btn-ver" style="margin-top: auto;">Ver detalles y editar →</div>
        @endif
    </div>

</div>
