<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-tik-red">Configuración</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Editar categoría</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-tik-red-dark">{{ session('status') }}</div>
            @endif
            <form method="POST" action="{{ route('package-categories.update', $category) }}" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @include('package-categories._form')
            </form>
        </div>
    </div>
</x-app-layout>
