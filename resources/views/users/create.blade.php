<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="text-sm font-semibold text-tik-red">Administración</p><h1 class="pt-1 text-2xl font-bold tracking-tight text-tik-ink">Nuevo usuario</h1><p class="pt-1 text-sm text-gray-500">Crea una cuenta para el equipo de Tik Shop.</p></div>
            <a href="{{ route('users.index') }}" class="text-sm font-semibold text-gray-600 hover:text-tik-red-dark">Volver al listado</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('users.store') }}" class="space-y-6 rounded-2xl border border-gray-200 border-t-tik-red bg-white p-5 shadow-sm sm:border-t-4 sm:p-6">
                @csrf
                @include('users._form', ['managedUser' => null])
                <div class="flex justify-end border-t border-gray-100 pt-6"><x-primary-button class="w-full justify-center px-6 py-3 sm:w-auto">Crear usuario</x-primary-button></div>
            </form>
        </div>
    </div>
</x-app-layout>
