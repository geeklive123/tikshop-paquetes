<div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-black/55 lg:hidden" @click="sidebarOpen = false" aria-hidden="true"></div>

<aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-black bg-tik-ink text-white transition-transform duration-200 ease-out lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" @keydown.escape.window="sidebarOpen = false">
    <div class="flex h-28 items-center justify-between border-b border-white/10 px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center" aria-label="Tik Shop">
            <img src="{{ asset('images/tikshop-logo.webp') }}" alt="Tik Shop" class="h-24 w-auto drop-shadow-md">
        </a>
        <button type="button" @click="sidebarOpen = false" class="rounded-lg p-2 text-gray-400 transition hover:bg-white/10 hover:text-white lg:hidden" aria-label="Cerrar menú">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </div>

    <nav class="flex-1 space-y-2 px-3 py-6" aria-label="Navegación principal">
        <a href="{{ route('dashboard') }}" @click="sidebarOpen = false" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-tik-red text-white shadow-sm shadow-black/30' => request()->routeIs('dashboard'), 'text-gray-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('dashboard')])>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12 12 3l9 9M5.25 10.5V21h13.5V10.5M9 21v-6h6v6" /></svg>
            Dashboard
        </a>
        <a href="{{ route('packages.index') }}" @click="sidebarOpen = false" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-tik-red text-white shadow-sm shadow-black/30' => request()->routeIs('packages.*'), 'text-gray-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('packages.*')])>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m3 7.5 9-4.5 9 4.5M3 7.5l9 4.5m-9-4.5V18l9 4m0-10 9-4.5M12 12v10m9-14.5V18l-9 4" /></svg>
            Paquetes
        </a>
        <a href="{{ route('pickup.scanner') }}" @click="sidebarOpen = false" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-tik-red text-white shadow-sm shadow-black/30' => request()->routeIs('pickup.*'), 'text-gray-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('pickup.*')])>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 7V4h3m10 0h3v3m0 10v3h-3M7 20H4v-3M7 8h2v2H7V8Zm8 0h2v2h-2V8ZM7 14h2v2H7v-2Zm7 0h3v3h-3v-3Zm-3-3h2v2h-2v-2Z" /></svg>
            Escanear QR
        </a>
        @if (Auth::user()->role->canManageUsers())
            <a href="{{ route('users.index') }}" @click="sidebarOpen = false" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-tik-red text-white shadow-sm shadow-black/30' => request()->routeIs('users.*'), 'text-gray-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('users.*')])>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87m-1-11.26a4 4 0 0 1 0 7.75" /></svg>
                Usuarios
            </a>
        @endif
    </nav>

    <div class="border-t border-white/10 p-4">
        <div class="rounded-xl bg-white/5 px-3 py-3">
            <p class="truncate text-sm font-semibold text-white">{{ Auth::user()->company?->name ?? 'Tik Shop' }}</p>
            <p class="pt-0.5 text-xs text-tik-box">{{ Auth::user()->role->label() }}</p>
        </div>
    </div>
</aside>
