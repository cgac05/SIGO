<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('auth_error') || $errors->any())
        <div class="bg-red-600 text-white font-bold text-lg px-6 py-4 rounded mb-4 border-4 border-red-800">
            <div class="flex items-center mb-2">
                <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span>ERROR DE AUTENTICACIÓN</span>
            </div>
            @if (session('auth_error'))
                <p>{{ session('auth_error') }}</p>
            @endif
            @if ($errors->any())
                <ul class="list-disc list-inside mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <form id="login-form" method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember" value="1" @checked(old('remember'))>
                <span class="ms-2 text-sm text-gray-600">{{ __('Mantener sesión iniciada') }}</span>
            </label>
        </div>

        <div class="grid gap-3 pt-2">
            <a href="{{ route('auth.google.redirect') }}" class="inline-flex w-full items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Continuar con Google
            </a>
        </div>

        <div class="flex items-center justify-end mt-4 gap-3">
            @if (Route::has('register'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('register') }}">
                    {{ __('¿Aún no tienes cuenta?') }}
                </a>
            @endif

            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Iniciar sesión') }}
            </x-primary-button>
        </div>
    </form>

    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
    <script>
        const loginForm = document.getElementById('login-form');
        let loginCaptchaResolved = false;

        loginForm.addEventListener('submit', function (event) {
            if (loginCaptchaResolved) {
                return;
            }

            event.preventDefault();

            grecaptcha.ready(function () {
                grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'login'}).then(function (token) {
                    document.getElementById('g-recaptcha-response').value = token;
                    loginCaptchaResolved = true;
                    loginForm.submit();
                });
            });
        });
    </script>
</x-guest-layout>
