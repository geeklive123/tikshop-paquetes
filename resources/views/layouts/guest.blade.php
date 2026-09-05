<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Tik Shop') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-tik-ink antialiased">
        <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-tik-gray px-4 py-10 sm:px-6">
            <div class="absolute inset-x-0 top-0 h-2 bg-tik-red"></div>
            <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-tik-box/15" aria-hidden="true"></div>
            <div class="absolute -bottom-32 -left-24 h-80 w-80 rounded-full bg-tik-red/5" aria-hidden="true"></div>

            <div class="relative w-full max-w-md">
                <div class="flex justify-center pb-7">
                    <a href="/" aria-label="Tik Shop">
                        <img src="{{ asset('images/tikshop-logo.webp') }}" alt="Tik Shop" class="h-36 w-auto drop-shadow-lg">
                    </a>
                </div>
                <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-xl shadow-black/10 sm:p-8">
                    <div class="absolute inset-x-0 top-0 h-1 bg-tik-red"></div>
                    {{ $slot }}
                </div>
                <p class="pt-6 text-center text-xs font-medium text-gray-500">Acceso exclusivo para personal autorizado</p>
            </div>
        </main>
    </body>
</html>
