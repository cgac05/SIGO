<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    @php($currentUser = Auth::user())
    @php($profilePhotoUrl = $currentUser?->avatar_url)
    @php($profilePhotoFallbackUrl = $currentUser?->avatar_placeholder_url)
    @php($showBeneficiaryProfileSections = $currentUser?->isBeneficiario())
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
    {{-- Solicitudes y Ciclos: SOLO para Directivo (Rol 2) --}}
    @if($currentUser?->isPersonal() && (int) optional($currentUser->personal)->fk_rol === 2)
        <x-nav-link :href="route('solicitudes.proceso.index')" :active="request()->routeIs('solicitudes.proceso.*')">
            {{ __('Solicitudes') }}
        </x-nav-link>
        <x-nav-link href="/admin/ciclos/1" :active="request()->is('admin/ciclos/*')">
            {{ __('Ciclos') }}
        </x-nav-link>
    @endif

    {{-- Dashboard: SOLO para Administrativo (Rol 1) --}}
    @if($currentUser?->isPersonal() && (int) optional($currentUser->personal)->fk_rol === 1)
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            {{ __('Dashboard') }}
        </x-nav-link>
    @endif

    {{-- Recursos Financieros: Solo para Valeria (Rol 3) --}}
    @if($currentUser?->isPersonal() && (int) optional($currentUser->personal)->fk_rol === 3)
        <x-nav-link :href="route('finanzas.panel')" :active="request()->routeIs('finanzas.*')">
            {{ __('Recursos Financieros') }}
        </x-nav-link>
    @endif
</div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-3">
                @if($currentUser)
                    <div
                        x-data="notificacionesBell()"
                        x-init="init()"
                        class="relative"
                    >
                        <button
                            @click="open = !open; if (open) { fetchItems(); }"
                            class="relative inline-flex items-center justify-center h-10 w-10 rounded-full border border-gray-200 text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                            title="Notificaciones"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15 17h5l-1.4-1.4a2 2 0 01-.6-1.4V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span
                                x-show="unreadCount > 0"
                                x-text="unreadCount > 99 ? '99+' : unreadCount"
                                class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] leading-[18px] text-center font-bold"
                            ></span>
                        </button>

                        <div
                            x-show="open"
                            @click.outside="open = false"
                            class="absolute right-0 mt-2 w-96 bg-white border border-gray-200 rounded-xl shadow-lg z-50"
                            style="display:none"
                        >
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                <h4 class="text-sm font-semibold text-gray-800">Notificaciones</h4>
                                <button @click="markAllRead()" class="text-xs font-semibold text-blue-700 hover:text-blue-800">Marcar todas</button>
                            </div>
                            <div class="max-h-96 overflow-auto">
                                <template x-if="items.length === 0">
                                    <p class="px-4 py-5 text-sm text-gray-500">No hay notificaciones.</p>
                                </template>
                                <template x-for="item in items" :key="item.id_notificacion">
                                    <button
                                        @click="markRead(item.id_notificacion)"
                                        class="w-full text-left px-4 py-3 border-b border-gray-100 hover:bg-gray-50"
                                    >
                                        <p class="text-sm text-gray-800" :class="item.leido ? '' : 'font-semibold'" x-text="item.mensaje"></p>
                                        <p class="text-xs text-gray-500 mt-1" x-text="item.fecha_creacion"></p>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                @endif

                <x-dropdown align="right" width="w-80">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <span class="flex h-8 w-8 shrink-0 overflow-hidden rounded-full border border-gray-200 bg-gray-100 shadow-sm">
                                <img
                                    src="{{ $profilePhotoUrl }}"
                                    alt="Foto de {{ $currentUser?->display_name ?? 'usuario' }}"
                                    class="h-full w-full object-cover"
                                    onerror="this.onerror=null;this.src='{{ $profilePhotoFallbackUrl }}';"
                                >
                            </span>

                            <div class="max-w-[10rem] truncate">
                                {{ $currentUser?->display_name ?? 'Usuario' }}
                            </div>

                            <div class="ms-1 shrink-0">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="border-b border-gray-100 px-4 py-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-14 w-14 shrink-0 overflow-hidden rounded-full border-2 border-gray-200 bg-gray-100 shadow-sm">
                                    <img
                                        src="{{ $profilePhotoUrl }}"
                                        alt="Foto de {{ $currentUser?->display_name ?? 'usuario' }}"
                                        class="h-full w-full object-cover"
                                        onerror="this.onerror=null;this.src='{{ $profilePhotoFallbackUrl }}';"
                                    >
                                </span>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ $currentUser?->display_name ?? 'Usuario' }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $currentUser?->email }}</p>
                                </div>
                            </div>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Perfil') }}
                        </x-dropdown-link>

                        <div class="border-t border-gray-100 py-2">
                            <p class="px-4 pb-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400">Secciones del perfil</p>
                            <a href="{{ route('profile.edit') }}#info" class="block w-full px-4 py-2 ps-8 pe-4 text-sm leading-5 text-gray-600 hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">Información</a>
                            <a href="{{ route('profile.edit') }}#photo" class="block w-full px-4 py-2 ps-8 pe-4 text-sm leading-5 text-gray-600 hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">Foto</a>
                            <a href="{{ route('profile.edit') }}#google" class="block w-full px-4 py-2 ps-8 pe-4 text-sm leading-5 text-gray-600 hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">Google</a>
                            <a href="{{ route('profile.edit') }}#security" class="block w-full px-4 py-2 ps-8 pe-4 text-sm leading-5 text-gray-600 hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">Seguridad</a>
                            @if($showBeneficiaryProfileSections)
                                <a href="{{ route('profile.edit') }}#arco" class="block w-full px-4 py-2 ps-8 pe-4 text-sm leading-5 text-gray-600 hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">Derechos ARCO</a>
                            @endif
                        </div>

                        <div class="border-t border-gray-100">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if($currentUser?->isBeneficiario() && $currentUser?->hasCompleteBeneficiarioProfile())
                <x-responsive-nav-link :href="route('apoyos.index')" :active="request()->routeIs('apoyos.*')">
                    {{ __('Apoyos') }}
                </x-responsive-nav-link>
            @endif

            {{-- Solicitudes: Solo para Personal que NO sea Financiero --}}
            @if($currentUser?->isPersonal() && (int) $currentUser?->tipo_usuario !== 3)
                <x-responsive-nav-link :href="route('solicitudes.proceso.index')" :active="request()->routeIs('solicitudes.proceso.*')">
                    {{ __('Solicitudes') }}
                </x-responsive-nav-link>
            @endif

            {{-- Ciclos: Solo para Personal que NO sea Financiero --}}
            @if($currentUser?->isPersonal() && (int) $currentUser?->tipo_usuario !== 3)
                <x-responsive-nav-link href="/admin/ciclos/1" :active="request()->is('admin/ciclos/*')">
                    {{ __('Ciclos') }}
                </x-responsive-nav-link>
            @endif

            {{-- Mostrar Recursos Financieros para Rol 3 --}}
            @if((int) $currentUser?->tipo_usuario === 3)
                <x-responsive-nav-link :href="route('finanzas.panel')" :active="request()->routeIs('finanzas.*')">
                    {{ __('Recursos Financieros') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ $currentUser?->display_name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ $currentUser?->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>

    <script>
        function notificacionesBell() {
            return {
                userId: {{ (int) ($currentUser?->id_usuario ?? 0) }},
                open: false,
                unreadCount: 0,
                items: [],
                init() {
                    this.fetchItems();
                    setInterval(() => this.fetchCount(), 15000);
                },
                async fetchItems() {
                    try {
                        const { data } = await window.axios.get('{{ route('api.notificaciones.index') }}');
                        this.items = data.data || [];
                        this.unreadCount = Number(data.unread_count || 0);
                    } catch (_) {}
                },
                async fetchCount() {
                    try {
                        const { data } = await window.axios.get('{{ route('api.notificaciones.noLeidas') }}');
                        this.unreadCount = Number(data.count || 0);
                    } catch (_) {}
                },
                async markRead(id) {
                    try {
                        await window.axios.post('{{ route('api.notificaciones.marcarLeida', 'ID') }}'.replace('ID', id));
                        await this.fetchItems();
                    } catch (_) {}
                }
            };
        }
    </script>
</nav>