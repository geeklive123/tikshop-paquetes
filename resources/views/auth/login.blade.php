<x-guest-layout>
    <div class="pb-7">
        <p class="text-sm font-semibold text-tik-red">Bienvenido</p>
        <h1 class="pt-1 text-2xl font-bold tracking-tight text-tik-ink">Iniciar sesión</h1>
        <p class="pt-2 text-sm leading-6 text-gray-500">Ingresa tus credenciales para administrar los paquetes.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <div class="flex items-center justify-between gap-4">
                <x-input-label for="password" value="Contraseña" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-semibold text-tik-red-dark transition hover:text-tik-red" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                @endif
            </div>
            <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <label for="remember_me" class="flex w-fit items-center gap-2 text-sm text-gray-600">
            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-tik-red shadow-sm focus:ring-tik-red" name="remember">
            Mantener sesión iniciada
        </label>
        <x-primary-button class="w-full justify-center py-3">Ingresar</x-primary-button>
    </form>
</x-guest-layout>
