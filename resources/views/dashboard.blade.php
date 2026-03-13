<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel principal') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    @if (session('error'))
                        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('status') === 'profile-completed')
                        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                            Tu perfil de beneficiario fue completado correctamente.
                        </div>
                    @endif

                    <div>
                        <p class="text-lg font-semibold">{{ $user->display_name }}</p>
                        <p class="text-sm text-gray-600">{{ $user->email }}</p>
                        <p class="mt-2 text-sm text-gray-500">Tipo de usuario: {{ $tipo }}</p>
                    </div>

                    @if ($user->isBeneficiario() && ! $user->hasCompleteBeneficiarioProfile())
                        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Debes completar tu perfil antes de registrar solicitudes de apoyo.
                            <a href="{{ route('registro.completar-perfil.show') }}" class="ml-2 font-semibold underline">Completar perfil</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
