<x-guest-layout>
    <form id="register-form" method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
        <x-auth-session-status class="mb-4" :status="session('status')" />

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <x-input-label for="name" :value="__('Nombre(s)')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
                <x-input-label for="apellido_paterno" :value="__('Apellido Paterno')" />
                <x-text-input id="apellido_paterno" class="block mt-1 w-full" type="text" name="apellido_paterno" required />
            </div>

            <div>
                <x-input-label for="apellido_materno" :value="__('Apellido Materno')" />
                <x-text-input id="apellido_materno" class="block mt-1 w-full" type="text" name="apellido_materno" required />
            </div>
        </div>

        <div class="mt-4">
            <x-input-label for="curp" :value="__('CURP')" />
            <x-text-input id="curp" class="block mt-1 w-full uppercase" type="text" name="curp" :value="old('curp')" maxlength="18" required />
            <p id="curp-feedback" class="mt-2 text-sm text-gray-500">La CURP debe pertenecer a una persona de 12 a 29 años.</p>
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
<<<<<<< HEAD
                <x-input-label for="phone" :value="__('Número de telefono')" />
                {{-- El campo se llama `telefono` para que coincida con la validación y el controlador --}}
                <x-text-input id="phone" class="block mt-1 w-full" type="text" name="telefono" :value="old('telefono')" required />
=======
                <x-input-label for="telefono" :value="__('Número de teléfono')" />
                <x-text-input id="telefono" class="block mt-1 w-full" type="text" name="telefono" :value="old('telefono')" required />
                <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
>>>>>>> 6da04ff4c21ec2e3298b12384bdb1b9c1fb7472c
            </div>

            <div>
                <x-input-label for="fecha_nacimiento" :value="__('Fecha de Nacimiento')" />
                <x-text-input id="fecha_nacimiento" class="block mt-1 w-full" type="date" name="fecha_nacimiento" readonly />
            </div>
            <input type="hidden" name="genero" id="genero">
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-4">
            <label for="acepta_privacidad" class="inline-flex items-start gap-3 text-sm text-gray-700">
                <input id="acepta_privacidad" name="acepta_privacidad" type="checkbox" class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" value="1" @checked(old('acepta_privacidad')) required>
                <span>Acepto el aviso de privacidad y autorizo el uso de mis datos para el trámite de apoyos.</span>
            </label>
            <x-input-error :messages="$errors->get('acepta_privacidad')" class="mt-2" />
        </div>

        <div class="grid gap-3 pt-2">
            <a href="{{ route('auth.google.redirect') }}" class="inline-flex w-full items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Continuar con Google
            </a>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('¿Ya tienes cuenta?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Registrar') }}
            </x-primary-button>
        </div>
    </form>

    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
    <script>
        const registerForm = document.getElementById('register-form');
        const inputTelefono = document.getElementById('telefono');
        const inputCurp = document.getElementById('curp');
        const inputFechaNacimiento = document.getElementById('fecha_nacimiento');
        const inputGenero = document.getElementById('genero');
        const curpFeedback = document.getElementById('curp-feedback');
        let registerCaptchaResolved = false;

        registerForm.addEventListener('submit', function (event) {
            if (registerCaptchaResolved) {
                return;
            }

            event.preventDefault();

            grecaptcha.ready(function() {
                grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'register'}).then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;
                    registerCaptchaResolved = true;
                    registerForm.submit();
                });
            });
        });

        inputTelefono.addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);

            if (!x[2]) {
                e.target.value = x[1] ? `(${x[1]}` : '';
            } else {
                e.target.value = x[3] ? `(${x[1]}) ${x[2]}-${x[3]}` : `(${x[1]}) ${x[2]}`;
            }
        });

        inputTelefono.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && e.target.value.length === 1) {
                e.target.value = '';
            }
        });

        inputCurp.addEventListener('input', function (e) {
            const curp = e.target.value.toUpperCase().trim();
            e.target.value = curp;

            const regex = /^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}$/;

            if (curp.length >= 10) {
                const year = parseInt(curp.substring(4, 6), 10);
                const month = curp.substring(6, 8);
                const day = curp.substring(8, 10);
                const currentYear = parseInt(new Date().getFullYear().toString().slice(-2), 10);
                const century = year > currentYear ? '19' : '20';
                const fullYear = `${century}${curp.substring(4, 6)}`;

                inputFechaNacimiento.value = `${fullYear}-${month}-${day}`;
            } else {
                inputFechaNacimiento.value = '';
            }

            const generoChar = curp.substring(10, 11);
            inputGenero.value = generoChar === 'H' || generoChar === 'M' ? generoChar : '';

            if (!regex.test(curp)) {
                curpFeedback.className = 'mt-2 text-sm text-amber-600';
                curpFeedback.textContent = 'Completa la CURP con formato válido para continuar.';

                return;
            }

            const birthDate = new Date(inputFechaNacimiento.value + 'T00:00:00');
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            if (age < 12 || age > 29) {
                curpFeedback.className = 'mt-2 text-sm text-red-600';
                curpFeedback.textContent = 'La persona debe tener entre 12 y 29 años para registrarse.';

                return;
            }

            curpFeedback.className = 'mt-2 text-sm text-green-600';
            curpFeedback.textContent = 'CURP válida para el rango de edad permitido.';
        });
    </script>
</x-guest-layout>
<<<<<<< HEAD
<!-- Este script formatea el número de teléfono a medida que el usuario escribe, siguiendo el formato (311)-111-11-11 -->
<script>
    const inputTelefono = document.getElementById('phone');

    // Máscara que produce el formato: (311) 123-4567
    inputTelefono.addEventListener('input', function (e) {
        let nums = e.target.value.replace(/\D/g, '');
        let parts = nums.match(/(\d{0,3})(\d{0,3})(\d{0,4})/);

        if (!parts) return;

        if (!parts[2]) {
            e.target.value = parts[1] ? `(${parts[1]}` : '';
        } else if (!parts[3]) {
            // (XXX) YYY
            e.target.value = `(${parts[1]}) ${parts[2]}`;
        } else {
            // (XXX) YYY-ZZZZ
            e.target.value = `(${parts[1]}) ${parts[2]}-${parts[3]}`;
        }
    });

    // Evitar que borren el paréntesis inicial si hay números
    inputTelefono.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && e.target.value.length === 1) {
            e.target.value = '';
        }
    });
    document.getElementById('curp').addEventListener('input', function (e) {
    let curp = e.target.value.toUpperCase();
    
    if (curp.length >= 10) {
        // Extraer año, mes y día
        let anio = curp.substring(4, 6);
        let mes  = curp.substring(6, 8);
        let dia  = curp.substring(8, 10);

        // Ajustar el siglo (Si el año es > 25, asumimos 1900, si no 2000)
        let siglo = parseInt(anio) > 25 ? '19' : '20';
        let fechaCompleta = `${siglo}${anio}-${mes}-${dia}`;

        document.getElementById('fecha_nacimiento').value = fechaCompleta;
    }
    let generoChar = curp.substring(10, 11); // Posición 11 de la CURP
        
        // Guardamos 'H' o 'M' en el campo oculto
        if (generoChar === 'H' || generoChar === 'M') {
            document.getElementById('genero').value = generoChar;
        } else {
            document.getElementById('genero').value = '';
        }
});
</script>
=======
>>>>>>> 6da04ff4c21ec2e3298b12384bdb1b9c1fb7472c
