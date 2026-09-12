<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-tik-red">Configuración</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Nueva categoría</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('package-categories.store') }}" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @include('package-categories._form', ['category' => new \App\Models\PackageCategory])
            </form>
        </div>
    </div>
</x-app-layout>
