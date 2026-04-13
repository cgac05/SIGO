<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mi Perfil - {{ config('app.name', 'SIGO') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        @include('layouts.navigation')

        <header class="bg-gradient-to-r from-blue-600 to-blue-800 shadow-lg">
            <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    <div class="flex h-28 w-28 shrink-0 overflow-hidden rounded-full border-2 border-white/80 bg-white/10 shadow-lg">
                        @if ($user?->getFotoUrl())
                            <img
                                src="{{ $user->getFotoUrl() }}"
                                alt="Foto de {{ $user?->display_name ?? 'usuario' }}"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <span class="flex h-full w-full items-center justify-center bg-white/10 text-3xl font-semibold text-white">
                                {{ mb_strtoupper(mb_substr($user?->display_name ?? $user?->email ?? 'U', 0, 1)) }}
                            </span>
                        @endif
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">Mi Perfil</h1>
                        <p class="text-blue-100 mt-1">Gestiona tu información personal y preferencias</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Tabs de navegación -->
            <div class="bg-white rounded-lg shadow mb-6 sticky top-0 z-10">
                <div class="flex flex-wrap border-b border-gray-200">
                    <a href="#info" onclick="switchTab('info')" class="flex-1 py-4 px-4 text-center font-medium text-gray-700 hover:text-blue-600 border-b-2 border-transparent hover:border-blue-300 transition cursor-pointer tab-button" data-tab="info">
                        👤 Información
                    </a>
                    <a href="#photo" onclick="switchTab('photo')" class="flex-1 py-4 px-4 text-center font-medium text-gray-700 hover:text-blue-600 border-b-2 border-transparent hover:border-blue-300 transition cursor-pointer tab-button" data-tab="photo">
                        🖼️ Foto
                    </a>
                    <a href="#google" onclick="switchTab('google')" class="flex-1 py-4 px-4 text-center font-medium text-gray-700 hover:text-blue-600 border-b-2 border-transparent hover:border-blue-300 transition cursor-pointer tab-button" data-tab="google">
                        🌐 Google
                    </a>
                    <a href="#security" onclick="switchTab('security')" class="flex-1 py-4 px-4 text-center font-medium text-gray-700 hover:text-blue-600 border-b-2 border-transparent hover:border-blue-300 transition cursor-pointer tab-button" data-tab="security">
                        🔐 Seguridad
                    </a>
                    <a href="#arco" onclick="switchTab('arco')" class="flex-1 py-4 px-4 text-center font-medium text-gray-700 hover:text-blue-600 border-b-2 border-transparent hover:border-blue-300 transition cursor-pointer tab-button" data-tab="arco">
                        ⚖️ Derechos ARCO
                    </a>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Tab: Información -->
                <div id="tab-info" class="tab-content">
                    <div class="p-6 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div class="max-w-3xl mx-auto">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>
                </div>

                <!-- Tab: Foto de Perfil -->
                <div id="tab-photo" class="tab-content hidden">
                    <div class="p-6 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div class="max-w-3xl mx-auto">
                            @include('profile.partials.profile-photo-form')
                        </div>
                    </div>
                </div>

                <!-- Tab: Vinculación Google -->
                <div id="tab-google" class="tab-content hidden">
                    <div class="p-6 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div class="max-w-3xl mx-auto">
                            @include('profile.partials.google-linking-form')
                        </div>
                    </div>
                </div>

                <!-- Tab: Seguridad -->
                <div id="tab-security" class="tab-content hidden">
                    <div class="p-6 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div class="max-w-3xl mx-auto">
                            @include('profile.partials.security-sessions-form')
                        </div>
                    </div>
                    
                    <div id="password-section" class="mt-6 p-6 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div class="max-w-3xl mx-auto">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>
                </div>

                <!-- Tab: Derechos ARCO -->
                <div id="tab-arco" class="tab-content hidden">
                    <div class="p-6 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div class="max-w-3xl mx-auto">
                            @include('profile.partials.arco-rights-form')
                        </div>
                    </div>
                </div>

                <!-- Peligro: Eliminar Cuenta -->
                <div class="p-6 sm:p-8 bg-red-50 border-l-4 border-red-500 rounded-lg">
                    <div class="max-w-3xl mx-auto">
                        <h3 class="text-lg font-medium text-red-900 mb-2">⚠️ Zona de Peligro</h3>
                        <p class="text-sm text-red-800 mb-4">
                            Acciones irreversibles para tu cuenta. Procede con cuidado.
                        </p>
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Tabs functionality
        const profileTabs = ['info', 'photo', 'google', 'security', 'arco'];

        function getProfileTabFromHash() {
            const hash = window.location.hash.replace('#', '');

            return profileTabs.includes(hash) ? hash : 'info';
        }

        document.addEventListener('DOMContentLoaded', function() {
            switchTab(getProfileTabFromHash());
        });

        window.addEventListener('hashchange', function() {
            switchTab(getProfileTabFromHash());
        });

        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });

            // Remove active border from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('border-blue-600', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-700');
            });

            const activeButton = document.querySelector('.tab-button[data-tab="' + tabName + '"]');

            // Show selected tab
            document.getElementById('tab-' + tabName).classList.remove('hidden');

            // Add active border to clicked button
            if (activeButton) {
                activeButton.classList.remove('border-transparent', 'text-gray-700');
                activeButton.classList.add('border-blue-600', 'text-blue-600');
            }

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
    <x-site-footer class="mt-16" />
</body>
</html>
