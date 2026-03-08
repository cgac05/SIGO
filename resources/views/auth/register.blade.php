<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
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
            <x-text-input id="curp" class="block mt-1 w-full uppercase" type="text" name="curp" maxlength="18" required />
        </div>
        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Correo electronico')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
         <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
                <x-input-label for="phone" :value="__('Número de telefono')" />
                <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" required />
            </div>

            <div ">
                <x-input-label for="fecha_nacimiento" :value="__('Fecha de Nacimiento')" />
                <x-text-input id="fecha_nacimiento" class="block mt-1 w-full" type="date" name="fecha_nacimiento" readonly />
            </div>
            <input type="hidden" name="genero" id="genero">
        </div>

       
        

        

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
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
</x-guest-layout>
<!-- Este script formatea el número de teléfono a medida que el usuario escribe, siguiendo el formato (311)-111-11-11 -->
<script>
    const inputTelefono = document.getElementById('phone');

    inputTelefono.addEventListener('input', function (e) {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
        
        
        if (!x[2]) {
            e.target.value = x[1] ? `(${x[1]}` : '';
        } else if (!x[3]) {
            e.target.value = `(${x[1]})-${x[2]}`;
        } else if (!x[4]) {
            e.target.value = `(${x[1]})-${x[2]}-${x[3]}`;
        } else {
            e.target.value = `(${x[1]})-${x[2]}-${x[3]}-${x[4]}`;
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
