<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{{ config('app.name', 'Laravel') }}</title>
                <link rel="preconnect" href="https://fonts.bunny.net">
                    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
                    <style>
                        :root {
                            --color-guinda: #611232;
                            --color-dorado: #f8b603;
                            --color-blanco: #ffffff;
                            --color-text: #1b1b18;
                            --color-gray: #706f6c;
                            --color-guinda-dark: #7f1c44;
                            --color-guinda-medium: #89254a;
                            --color-text-light: #ffe8cd;
                            --color-light: #f8f1e6;
                        }
                        body { background: var(--color-blanco); color: var(--color-text); }
                    </style>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
                @else
                        <style>
                                    /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */
                                            </style>
                                                @endif
                                                </head>
                                                <body class="bg-white text-[var(--color-text)] min-h-screen">
                                                    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                                                            <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-10">
                                                                        <div class="flex items-center gap-3">
                                                                                        <div class="w-10 h-10 rounded-full bg-[var(--color-guinda)] flex items-center justify-center text-white font-bold">S</div>
                                                                                                        <div>
                                                                                                                            <p class="text-sm font-semibold text-[var(--color-guinda)]">SIGO</p>
                                                                                                                                                <p class="text-xs text-[var(--color-gray)]">Sistema Integral de Apoyos Juveniles</p>
                                                                                                                                                                </div>
                                                                                                                                                                            </div>
                                                                                                                                                                                        @if (Route::has('login'))
                                                                                                                                                                                                        <nav class="flex items-center gap-2">
                                                                                                                                                                                                                            @auth
                                                                                                                                                                                                                                                    <a href="{{ url('/dashboard') }}" class="px-4 py-2 rounded-lg bg-[var(--color-guinda)] text-white text-sm font-semibold hover:bg-[var(--color-guinda-dark)] transition">Dashboard</a>
                                                                                                                                                                                                                                                                        @else
                                                                                                                                                                                                                                                                                                <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg bg-[var(--color-guinda)] text-white text-sm font-semibold hover:bg-[var(--color-guinda-dark)] transition">Iniciar Sesión</a>
                                                                                                                                                                                                                                                                                                                        @if (Route::has('register'))
                                                                                                                                                                                                                                                                                                                                                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-lg border border-[var(--color-guinda)] text-[var(--color-guinda)] text-sm font-semibold hover:bg-[var(--color-guinda)] hover:text-white transition">Regístrate ahora</a>
                                                                                                                                                                                                                                                                                                                                                                            @endif
                                                                                                                                                                                                                                                                                                                                                                                                @endauth
                                                                                                                                                                                                                                                                                                                                                                                                                </nav>
                                                                                                                                                                                                                                                                                                                                                                                                                            @endif
                                                                                                                                                                                                                                                                                                                                                                                                                                    </header>
                                                                                                                                                                                                                                                                                                                                                                                                                                    
        <main class="space-y-10">
                    <section class="rounded-3xl bg-gradient-to-br from-[var(--color-guinda)] via-[var(--color-guinda-medium)] to-[var(--color-dorado)] p-8 text-white shadow-lg">
                                    <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
                                                        <div>
                                                                                <h1 class="text-3xl font-extrabold sm:text-4xl">Bienvenido a SIGO: Tu Portal de Apoyos Juveniles</h1>
                                                                                                        <p class="mt-4 text-lg text-[var(--color-text-light)]">Transformando el presente de la juventud nayarita con programas transparentes y acceso directo a apoyos.</p>
                                                                                                                                <div class="mt-6 flex flex-wrap gap-3">
                                                                                                                                                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-sm font-semibold text-[var(--color-guinda)] hover:bg-[var(--color-light)] transition">Iniciar Sesión</a>
                                                                                                                                                                                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-lg border border-white bg-transparent px-6 py-3 text-sm font-semibold text-white hover:bg-white hover:text-[var(--color-guinda)] transition">Regístrate ahora</a>
                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                        <img src="{{ asset('images/main.jpg') }}" alt="Juventud trabajando" class="h-52 w-full max-w-md rounded-2xl object-cover shadow-xl lg:h-64" />
                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                    </section>
                                                                                                                                                                                                                                                                                    
            <section class="grid gap-8 lg:grid-cols-2">
                            <article class="rounded-2xl bg-white p-6 shadow-lg border border-[var(--color-dorado)]">
                                                <h2 class="text-2xl font-bold text-[var(--color-guinda)]">¿Quiénes Somos?</h2>
                                                                    <p class="mt-4 text-sm leading-relaxed text-[var(--color-text)]">El Instituto Nayarita de la Juventud (INJUVE) es un organismo público descentralizado que funge como el ente rector de las políticas públicas destinadas al desarrollo integral de la población joven en el estado de Nayarit. Su objetivo primordial es diseñar y ejecutar acciones que propicien la superación física, intelectual y económica de los jóvenes, garantizando su incorporación plena al desarrollo estatal.</p>
                                                                                    </article>
                                                                                                    <article class="rounded-2xl bg-white p-6 shadow-lg border border-[var(--color-dorado)]">
                                                                                                                        <h2 class="text-2xl font-bold text-[var(--color-guinda)]">Misión y Objetivos</h2>
                                                                                                                                            <ul class="mt-4 space-y-3 text-sm leading-relaxed text-[var(--color-text)]">
                                                                                                                                                                    <li> Fomentar la participación social y comunitaria de jóvenes de 12 a 29 años.</li>
                                                                                                                                                                                            <li> Promover oportunidades de empleo, educación, cultura y salud.</li>
                                                                                                                                                                                                                    <li> Garantizar acceso transparente a apoyos y asesoría profesional.</li>
                                                                                                                                                                                                                                        </ul>
                                                                                                                                                                                                                                                        </article>
                                                                                                                                                                                                                                                                    </section>
                                                                                                                                                                                                                                                                    
            <section class="rounded-2xl bg-white p-6 shadow-lg border border-[var(--color-guinda)]">
                            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                                                <div>
                                                                        <h2 class="text-2xl font-bold text-[var(--color-guinda)]">Nuestro Impacto</h2>
                                                                                                <p class="mt-2 text-sm text-[var(--color-text)]">SIGO cambia vidas con servicios para tu desarrollo integral.</p>
                                                                                                                    </div>
                                                                                                                                        <div class="rounded-xl bg-[#f8e7b0] px-4 py-3 text-center border border-[var(--color-dorado)]">
                                                                                                                                                                <p class="text-2xl font-bold text-[#774a00]" id="counter">{{ number_format($beneficiariosCount) }}</p>
                                                                                                                                                                                        <p class="text-xs font-medium text-[#6b4a00]">Jóvenes Beneficiados</p>
                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                            
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                    <div class="rounded-xl border border-[var(--color-dorado)] bg-white p-4 text-center text-[var(--color-guinda)] hover:bg-[#fff8e0] transition">
                                                            <p class="text-3xl"></p>
                                                                                    <p class="mt-2 font-semibold">Empleo</p>
                                                                                                        </div>
                                                                                                                            <div class="rounded-xl border border-[var(--color-dorado)] bg-white p-4 text-center text-[var(--color-guinda)] hover:bg-[#fff8e0] transition">
                                                                                                                                                    <p class="text-3xl"></p>
                                                                                                                                                                            <p class="mt-2 font-semibold">Educación</p>
                                                                                                                                                                                                </div>
                                                                                                                                                                                                                    <div class="rounded-xl border border-[var(--color-dorado)] bg-white p-4 text-center text-[var(--color-guinda)] hover:bg-[#fff8e0] transition">
                                                                                                                                                                                                                                            <p class="text-3xl"></p>
                                                                                                                                                                                                                                                                    <p class="mt-2 font-semibold">Cultura</p>
                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                            <div class="rounded-xl border border-[var(--color-dorado)] bg-white p-4 text-center text-[var(--color-guinda)] hover:bg-[#fff8e0] transition">
                                                                                                                                                                                                                                                                                                                                    <p class="text-3xl"></p>
                                                                                                                                                                                                                                                                                                                                                            <p class="mt-2 font-semibold">Salud</p>
                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                            </section>
                                                                                                                                                                                                                                                                                                                                                                                                            
            <section class="rounded-2xl bg-[#fff2f2] p-6 shadow-lg border border-[var(--color-guinda)]">
                            <h2 class="text-2xl font-bold text-[var(--color-guinda)]">Sección SIGO - Invitación a Apoyos</h2>
                                            <p class="mt-3 text-sm text-[var(--color-text)]">¿Tienes entre 12 y 29 años? SIGO es la herramienta diseñada para que accedas de forma transparente a los apoyos económicos y en especie que el Gobierno de Nayarit tiene para ti.</p>
                                                            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                                                                <div class="rounded-xl bg-white p-4 shadow-sm"> <h3 class="font-semibold">Becas Educativas</h3> <p class="text-xs mt-1">Apoyos para continuar tus estudios.</p></div>
                                                                                                    <div class="rounded-xl bg-white p-4 shadow-sm"> <h3 class="font-semibold">Estímulos de Salud</h3> <p class="text-xs mt-1">Programas preventivos y atención integral.</p></div>
                                                                                                                        <div class="rounded-xl bg-white p-4 shadow-sm"> <h3 class="font-semibold">Formación Laboral</h3> <p class="text-xs mt-1">Cursos y vinculación con empresas.</p></div>
                                                                                                                                            <div class="rounded-xl bg-white p-4 shadow-sm"> <h3 class="font-semibold">Cultura y Deporte</h3> <p class="text-xs mt-1">Actividades para el desarrollo integral.</p></div>
                                                                                                                                                            </div>
                                                                                                                                                                        </section>
                                                                                                                                                                        
            <section class="rounded-2xl bg-white p-6 shadow-lg border border-[var(--color-dorado)]">
                            <h2 class="text-2xl font-bold text-[var(--color-guinda)]">Contacto, Ubicación y Redes</h2>
                                            <div class="mt-4 grid gap-6 lg:grid-cols-2">
                                                                <div class="space-y-2 text-sm text-[var(--color-text)]">
                                                                                        <p><strong>Dirección:</strong> Calle Jiquilpan No. 137, Colonia Lázaro Cárdenas, C.P. 63190, Tepic, Nayarit.</p>
                                                                                                                <p><strong>Teléfono:</strong> 311 169 3151</p>
                                                                                                                                        <p><strong>Horario:</strong> Lunes a viernes de 8:00 AM a 4:00 PM</p>
                                                                                                                                                                <p><strong>Email:</strong> direccion.injuve.nay@gmail.com</p>
                                                                                                                                                                                        <p><strong>Redes:</strong> Facebook INJUVE Nayarit | Instagram @injuvenayarit | X @INJUVENayarit</p>
                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3734.827568138631!2d-104.8910179238087!3d21.499392785615987!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x842b1dc587e07b1d%3A0x4aaf6b95aad1a7fa!2sInstituto%20Nayarita%20de%20la%20Juventud!5e0!3m2!1ses-419!2smx!4v1700000000000!5m2!1ses-419!2smx" width="100%" height="240" class="rounded-xl border border-[var(--color-dorado)]" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                            </section>
                                                                                                                                                                                                                                                            
        </main>
        
        <footer class="mt-16 rounded-2xl bg-[var(--color-guinda)] p-6 text-white shadow-lg">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                                        <p class="font-semibold">INJUVE Nayarit</p>
                                                                            <p class="text-xs"> {{ date('Y') }} Gobierno del Estado de Nayarit. Todos los derechos reservados.</p>
                                                                                            </div>
                                                                                                            <div class="flex items-center gap-3 text-xs">
                                                                                                                                <span>Síguenos:</span>
                                                                                                                                                    <a href="#" class="opacity-90 hover:opacity-100">Facebook</a>
                                                                                                                                                                        <a href="#" class="opacity-90 hover:opacity-100">Instagram</a>
                                                                                                                                                                                            <a href="#" class="opacity-90 hover:opacity-100">X</a>
                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                            <div class="flex items-center gap-3 text-xs">
                                                                                                                                                                                                                                                <a href="#" class="underline">Aviso de Privacidad</a>
                                                                                                                                                                                                                                                                    <a href="#" class="underline">Términos de Servicio</a>
                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                        </footer>
                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                            
    <script>
            document.addEventListener('DOMContentLoaded', function () {
                        const counterEl = document.getElementById('counter');
                                    const target = 2378;
                                                let current = 0;
                                                            const step = Math.ceil(target / 80);
                                                                        const timer = setInterval(() => {
                                                                                        current += step;
                                                                                                        if (current >= target) {
                                                                                                                            current = target;
                                                                                                                                                clearInterval(timer);
                                                                                                                                                                }
                                                                                                                                                                                counterEl.textContent = current.toLocaleString();
                                                                                                                                                                                            }, 25);
                                                                                                                                                                                                    });
                                                                                                                                                                                                        </script>
                                                                                                                                                                                                        </body>
                                                                                                                                                                                                        </html>
                                                                                                                                                                                                        