<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-tik-red">Configuración</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Categorías de paquetes</h1>
                <p class="mt-1 text-sm text-gray-500">Administra tamaños, rangos y precios de almacenaje.</p>
            </div>
            <a href="{{ route('package-categories.create') }}" class="inline-flex items-center justify-center rounded-xl bg-tik-red px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-tik-red-dark">Nueva categoría</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-tik-red-dark">{{ session('status') }}</div>
            @endif
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($categories as $category)
                    <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="h-2" style="background-color: {{ $category->color ?: '#6B7280' }}"></div>
                        <div class="space-y-4 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="text-lg font-bold text-tik-ink">{{ $category->name }}</h2>
                                <span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-green-50 text-green-700' => $category->active, 'bg-gray-100 text-gray-600' => ! $category->active])>{{ $category->active ? 'Activa' : 'Inactiva' }}</span>
                            </div>
                            <dl class="grid grid-cols-2 gap-4 text-sm">
                                <div><dt class="text-gray-500">Rango</dt><dd class="mt-1 font-mono font-bold text-tik-ink">{{ $category->codeRange() }}</dd></div>
                                <div><dt class="text-gray-500">Precio</dt><dd class="mt-1 font-bold text-tik-red-dark">Bs {{ number_format((float) $category->price, 2) }}</dd></div>
                            </dl>
                            <a href="{{ route('package-categories.edit', $category) }}" class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Editar</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
