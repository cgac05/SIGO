<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="Content-Security-Policy" content="default-src 'self' blob: data:; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: data: http://localhost:5173 http://127.0.0.1:5173 https://www.gstatic.com https://apis.google.com https://accounts.google.com https://www.google.com https://cdn.quilljs.com https://cdn.jsdelivr.net; script-src-elem 'self' 'unsafe-inline' 'unsafe-eval' blob: data: http://localhost:5173 http://127.0.0.1:5173 https://www.gstatic.com https://apis.google.com https://accounts.google.com https://www.google.com https://cdn.quilljs.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' http://localhost:5173 http://127.0.0.1:5173 https://fonts.bunny.net https://cdn.quilljs.com https://cdn.jsdelivr.net; style-src-elem 'self' 'unsafe-inline' http://localhost:5173 http://127.0.0.1:5173 https://fonts.bunny.net https://cdn.quilljs.com https://cdn.jsdelivr.net; font-src 'self' data: https://fonts.bunny.net; img-src 'self' data: blob: https:; connect-src 'self' ws://localhost:5173 ws://127.0.0.1:5173 http://localhost:5173 http://127.0.0.1:5173 https://www.googleapis.com https://apis.google.com https://accounts.google.com https://www.google.com https://cdn.quilljs.com https://cdn.jsdelivr.net; frame-src 'self' https://accounts.google.com https://www.google.com https://docs.google.com https://drive.google.com https://apis.google.com;">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
        <body class="font-sans antialiased">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                @yield('content')
            </main>
        </div>

        {{-- Modal cambio de contraseña obligatorio --}}
        @include('components.modal-cambio-password')

    </body>
</html>