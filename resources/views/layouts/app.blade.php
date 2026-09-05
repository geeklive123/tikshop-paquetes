<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Tik Shop') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-tik-gray font-sans text-tik-ink antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen">
            @include('layouts.navigation')

            <div class="lg:pl-64">
                <header class="sticky top-0 z-20 border-b border-gray-200 bg-white/95 backdrop-blur">
                    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                        <div class="flex min-w-0 items-center gap-3">
                            <button type="button" @click="sidebarOpen = true" class="inline-flex rounded-lg p-2 text-gray-500 transition hover:bg-tik-gray hover:text-tik-ink focus:outline-none focus:ring-2 focus:ring-tik-red lg:hidden" aria-label="Abrir menú">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 12h16M4 18h16" /></svg>
                            </button>
                            <img src="{{ asset('images/tikshop-logo.webp') }}" alt="Tik Shop" class="h-12 w-auto lg:hidden">
                        </div>

                        <x-dropdown align="right" width="56">
                            <x-slot name="trigger">
                                <button type="button" class="flex items-center gap-3 rounded-xl px-2 py-1.5 text-left transition hover:bg-tik-gray focus:outline-none focus:ring-2 focus:ring-tik-red">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-red-50 text-sm font-bold text-tik-red-dark">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                                    <span class="hidden min-w-0 sm:block">
                                        <span class="block max-w-44 truncate text-sm font-semibold text-tik-ink">{{ Auth::user()->name }}</span>
                                        <span class="block text-xs text-gray-500">{{ Auth::user()->role->label() }}</span>
                                    </span>
                                    <svg class="hidden h-4 w-4 text-gray-400 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="border-b border-gray-100 px-4 py-3">
                                    <p class="truncate text-sm font-semibold text-tik-ink">{{ Auth::user()->name }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ Auth::user()->email }}</p>
                                </div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2.5 text-left text-sm text-gray-700 transition hover:bg-red-50 hover:text-tik-red-dark">Cerrar sesión</button>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </header>

                @isset($header)
                    <div class="border-b border-gray-200 bg-white">
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{{ $header }}</div>
                    </div>
                @endisset

                <main>{{ $slot }}</main>
            </div>
        </div>
    </body>
</html>
