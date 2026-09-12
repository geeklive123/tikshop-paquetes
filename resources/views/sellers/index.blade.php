<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-tik-red">Directorio comercial</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Vendedores</h1>
                <p class="mt-1 text-sm text-gray-500">Administra las personas y negocios que dejan paquetes.</p>
            </div>
            <a href="{{ route('sellers.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-tik-red px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-tik-red-dark"><span aria-hidden="true">+</span> Nuevo vendedor</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-tik-red-dark">{{ session('status') }}</div>
            @endif

            <form method="GET" action="{{ route('sellers.index') }}" class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row">
                <label for="search" class="sr-only">Buscar vendedor</label>
                <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Nombre, negocio, celular o documento" class="min-w-0 flex-1 rounded-xl border-gray-300 text-sm shadow-sm focus:border-tik-red focus:ring-tik-red">
                <button class="rounded-xl bg-tik-ink px-5 py-2.5 text-sm font-semibold text-white hover:bg-black">Buscar</button>
                @if ($search !== '')
                    <a href="{{ route('sellers.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">Limpiar</a>
                @endif
            </form>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                @if ($sellers->isEmpty())
                    <div class="px-6 py-14 text-center"><p class="font-semibold text-tik-ink">No se encontraron vendedores.</p></div>
                @else
                    <div class="divide-y divide-gray-100 md:hidden">
                        @foreach ($sellers as $seller)
                            <article class="space-y-4 p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0"><p class="truncate font-semibold text-tik-ink">{{ $seller->name }}</p><p class="truncate text-sm text-gray-500">{{ $seller->business_name ?: 'Sin negocio registrado' }}</p></div>
                                    <span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-green-50 text-green-700' => $seller->active, 'bg-gray-100 text-gray-600' => ! $seller->active])>{{ $seller->active ? 'Activo' : 'Inactivo' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3 text-sm"><span class="text-gray-600">{{ $seller->phone }}</span><span class="font-semibold text-tik-ink">{{ $seller->packages_count }} paquetes</span></div>
                                <div class="flex items-center gap-4"><a href="{{ route('sellers.show', $seller) }}" class="text-sm font-semibold text-tik-red-dark">Ver</a>@can('update', $seller)<a href="{{ route('sellers.edit', $seller) }}" class="text-sm font-semibold text-gray-600">Editar</a>@endcan</div>
                            </article>
                        @endforeach
                    </div>
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-tik-gray"><tr>@foreach (['Nombre', 'Negocio', 'Celular', 'Estado', 'Paquetes', 'Acciones'] as $heading)<th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $heading }}</th>@endforeach</tr></thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($sellers as $seller)
                                    <tr class="hover:bg-red-50/40">
                                        <td class="px-6 py-4 text-sm font-semibold text-tik-ink">{{ $seller->name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $seller->business_name ?: '—' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $seller->phone }}</td>
                                        <td class="px-6 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-green-50 text-green-700' => $seller->active, 'bg-gray-100 text-gray-600' => ! $seller->active])>{{ $seller->active ? 'Activo' : 'Inactivo' }}</span></td>
                                        <td class="px-6 py-4 text-sm font-semibold text-tik-ink">{{ $seller->packages_count }}</td>
                                        <td class="px-6 py-4"><div class="flex gap-4"><a href="{{ route('sellers.show', $seller) }}" class="text-sm font-semibold text-tik-red-dark">Ver</a>@can('update', $seller)<a href="{{ route('sellers.edit', $seller) }}" class="text-sm font-semibold text-gray-600">Editar</a>@endcan</div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
            {{ $sellers->links() }}
        </div>
    </div>
</x-app-layout>
