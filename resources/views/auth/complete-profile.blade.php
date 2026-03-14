<x-guest-layout>
    <form method="POST" action="{{ route('registro.completar-perfil.store') }}" class="space-y-4">
        @csrf

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-1">
            <h1 class="text-xl font-semibold text-gray-900">Completa tu perfil</h1>
            <p class="text-sm text-gray-600">Tu cuenta ya fue autenticada. Falta vincular tu información como beneficiario para habilitar los módulos de solicitudes.</p>
            <p class="text-sm text-gray-500">Correo asociado: {{ $user->email }}</p>
        </div>

        <div>
            <x-input-label for="name" :value="__('Nombre(s)')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
                <x-input-label for="apellido_paterno" :value="__('Apellido Paterno')" />
                <x-text-input id="apellido_paterno" class="block mt-1 w-full" type="text" name="apellido_paterno" :value="old('apellido_paterno')" required />
            </div>

            <div>
                <x-input-label for="apellido_materno" :value="__('Apellido Materno')" />
                <x-text-input id="apellido_materno" class="block mt-1 w-full" type="text" name="apellido_materno" :value="old('apellido_materno')" required />
            </div>
        </div>

        <div class="mt-4">
            <x-input-label for="curp" :value="__('CURP')" />
            <x-text-input id="curp" class="block mt-1 w-full uppercase" type="text" name="curp" :value="old('curp')" maxlength="18" required />
            <p id="curp-feedback" class="mt-2 text-sm text-gray-500">La CURP debe pertenecer a una persona de 12 a 29 años.</p>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
                <x-input-label for="telefono" :value="__('Número de teléfono')" />
                <x-text-input id="telefono" class="block mt-1 w-full" type="text" name="telefono" :value="old('telefono')" required />
                <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="fecha_nacimiento" :value="__('Fecha de Nacimiento')" />
                <x-text-input id="fecha_nacimiento" class="block mt-1 w-full" type="date" name="fecha_nacimiento" readonly />
            </div>
        </div>

        <div class="mt-4">
            <label for="acepta_privacidad" class="inline-flex items-start gap-3 text-sm text-gray-700">
                <input id="acepta_privacidad" name="acepta_privacidad" type="checkbox" class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" value="1" @checked(old('acepta_privacidad')) required>
                <span>Acepto el aviso de privacidad y autorizo el uso de mis datos para el trámite de apoyos.</span>
            </label>
            <x-input-error :messages="$errors->get('acepta_privacidad')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-4">
                {{ __('Guardar perfil') }}
            </x-primary-button>
        </div>
    </form>

    <script>
        const profileTelefono = document.getElementById('telefono');
        const profileCurp = document.getElementById('curp');
        const profileBirthDate = document.getElementById('fecha_nacimiento');
        const profileCurpFeedback = document.getElementById('curp-feedback');

        profileTelefono.addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);

            if (!x[2]) {
                e.target.value = x[1] ? `(${x[1]}` : '';
            } else {
                e.target.value = x[3] ? `(${x[1]}) ${x[2]}-${x[3]}` : `(${x[1]}) ${x[2]}`;
            }
        });

        profileCurp.addEventListener('input', function (e) {
            const curp = e.target.value.toUpperCase().trim();
            e.target.value = curp;

            const regex = /^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}$/;

            if (curp.length >= 10) {
                const year = parseInt(curp.substring(4, 6), 10);
                const month = curp.substring(6, 8);
                const day = curp.substring(8, 10);
                const currentYear = parseInt(new Date().getFullYear().toString().slice(-2), 10);
                const century = year > currentYear ? '19' : '20';
                profileBirthDate.value = `${century}${curp.substring(4, 6)}-${month}-${day}`;
            } else {
                profileBirthDate.value = '';
            }

            if (!regex.test(curp)) {
                profileCurpFeedback.className = 'mt-2 text-sm text-amber-600';
                profileCurpFeedback.textContent = 'Completa la CURP con formato válido para continuar.';

                return;
            }

            const birthDate = new Date(profileBirthDate.value + 'T00:00:00');
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            if (age < 12 || age > 29) {
                profileCurpFeedback.className = 'mt-2 text-sm text-red-600';
                profileCurpFeedback.textContent = 'La persona debe tener entre 12 y 29 años para registrarse.';

                return;
            }

            profileCurpFeedback.className = 'mt-2 text-sm text-green-600';
            profileCurpFeedback.textContent = 'CURP válida para el rango de edad permitido.';
        });
    </script>
</x-guest-layout>