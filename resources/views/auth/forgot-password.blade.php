<x-guest-layout>
    <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-6 text-slate-600 shadow-sm">
        @if ($step === 'code')
            <p class="font-semibold text-slate-700">{{ __('Se ha enviado un código de restablecimiento a su correo electrónico.') }}</p>
            <p class="mt-1">{{ __('Ingrese el código de 6 dígitos para continuar con el proceso y establecer una nueva contraseña.') }}</p>
            @if ($resetEmail)
                <p class="mt-2 text-xs text-slate-500">{{ __('Correo registrado:') }} <span class="font-semibold text-slate-700">{{ $resetEmail }}</span></p>
            @endif
        @else
            <p class="font-semibold text-slate-700">{{ __('Si ha olvidado su contraseña, no se preocupe.') }}</p>
            <p class="mt-1">{{ __('Indique su correo electrónico y le enviaremos un código seguro de 6 dígitos para validar su identidad.') }}</p>
        @endif
    </div>

    @if (session('warning'))
        <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-900 shadow-sm">
            <p class="font-semibold">{{ __('Aviso') }}</p>
            <p class="mt-1">{{ session('warning') }}</p>
        </div>
    @endif

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ $step === 'code' ? route('password.code.verify') : route('password.email') }}">
        @csrf

        @if ($step === 'code')
            <div>
                <x-input-label for="codigo" :value="__('Código de restablecimiento')" />
                <x-text-input
                    id="codigo"
                    class="block mt-1 w-full tracking-[0.4em] text-center text-lg"
                    type="text"
                    name="codigo"
                    :value="old('codigo')"
                    required
                    autofocus
                    inputmode="numeric"
                    maxlength="6"
                    autocomplete="one-time-code"
                    placeholder="000000"
                />
                <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
            </div>
        @else
            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Correo electrónico')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="email" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
        @endif

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ $step === 'code' ? __('Verificar código') : __('Enviar código de restablecimiento') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
