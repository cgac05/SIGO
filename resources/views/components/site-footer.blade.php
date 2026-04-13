@php
    $currentYear = date('Y');
@endphp

<footer {{ $attributes->merge(['class' => 'border-t border-white/10 bg-gradient-to-br from-[#001259] via-[#081a6f] to-[#000833] text-white shadow-[0_-12px_40px_rgba(17,17,24,0.16)]']) }}>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/95 p-2 shadow-lg ring-1 ring-white/20">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo SIGO" class="h-10 w-auto">
                    </div>

                    <div>
                        <p class="text-xs font-semibold tracking-[0.28em] text-white/65">power by SIGO</p>
                        <h3 class="mt-1 text-xl font-semibold leading-tight text-white">Plataforma Estatal de Juventud</h3>
                    </div>
                </div>

                <p class="mt-4 max-w-md text-sm leading-6 text-white/80">
                    Plataforma institucional del INJUVE Nayarit para la atención, seguimiento y gestión de apoyos juveniles.
                </p>
            </div>

            <div class="lg:col-span-4">
                <h3 class="text-sm font-semibold uppercase tracking-[0.22em] text-[#f8b603]">Información de contacto</h3>

                <dl class="mt-4 space-y-3 text-sm text-white/85">
                    <div class="flex gap-3">
                        <dt class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-[#f8b603]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </dt>
                        <dd>Calle Jiquilpan No. 137, Colonia Lázaro Cárdenas, C.P. 63190, Tepic, Nayarit.</dd>
                    </div>

                    <div class="flex gap-3">
                        <dt class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-[#f8b603]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h2.28a1 1 0 01.95.68l1.2 3.6a1 1 0 01-.45 1.15l-2.02 1.21a11.042 11.042 0 005.5 5.5l1.21-2.02a1 1 0 011.15-.45l3.6 1.2a1 1 0 01.68.95V19a2 2 0 01-2 2h-1C9.82 21 3 14.18 3 6V5z" />
                            </svg>
                        </dt>
                        <dd><a href="tel:+523111693151" class="transition hover:text-white">311 169 3151</a></dd>
                    </div>

                    <div class="flex gap-3">
                        <dt class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-[#f8b603]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </dt>
                        <dd>Lunes a viernes de 8:00 AM a 4:00 PM</dd>
                    </div>

                    <div class="flex gap-3">
                        <dt class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-[#f8b603]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </dt>
                        <dd><a href="mailto:direccion.injuve.nay@gmail.com" class="transition hover:text-white">direccion.injuve.nay@gmail.com</a></dd>
                    </div>
                </dl>
            </div>

            <div class="lg:col-span-4">
                <h3 class="text-sm font-semibold uppercase tracking-[0.22em] text-[#f8b603]">Redes sociales</h3>

                <div class="mt-4 space-y-3 text-sm">
                    <a href="https://www.facebook.com/injuve/?locale=es_LA" target="_blank" rel="noreferrer" class="group flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3 transition hover:bg-white/10">
                        <span class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#1877f2]/20 text-sm font-bold text-[#9fc5ff]">f</span>
                            <span>
                                <span class="block font-semibold text-white">Facebook</span>
                                <span class="block text-xs text-white/65">INJUVE Nayarit</span>
                            </span>
                        </span>
                        <span class="text-white/40 transition group-hover:text-white/80">↗</span>
                    </a>

                    <a href="https://www.instagram.com/injuve_nayarit/" target="_blank" rel="noreferrer" class="group flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3 transition hover:bg-white/10">
                        <span class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-sm font-bold text-white">ig</span>
                            <span>
                                <span class="block font-semibold text-white">Instagram</span>
                                <span class="block text-xs text-white/65">@injuvenayarit</span>
                            </span>
                        </span>
                        <span class="text-white/40 transition group-hover:text-white/80">↗</span>
                    </a>

                    <a href="https://x.com/INJUVENayarit" target="_blank" rel="noreferrer" class="group flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3 transition hover:bg-white/10">
                        <span class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-sm font-bold text-white">x</span>
                            <span>
                                <span class="block font-semibold text-white">X</span>
                                <span class="block text-xs text-white/65">@INJUVENayarit</span>
                            </span>
                        </span>
                        <span class="text-white/40 transition group-hover:text-white/80">↗</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-8 flex flex-col gap-3 border-t border-white/10 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-white/70">© {{ $currentYear }} SIGO - Instituto Nayarita de la Juventud. Todos los derechos reservados.</p>
            <p class="text-xs text-white/50">Diseñado para facilitar el acceso a apoyos, servicios y gestión institucional.</p>
        </div>
    </div>
</footer>