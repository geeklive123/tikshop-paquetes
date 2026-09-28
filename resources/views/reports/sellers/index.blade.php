<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-tik-red">Reportes</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Vendedores</h1><p class="mt-1 text-sm text-gray-500">Actividad y monto de almacenaje asociado por vendedor.</p></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @include('reports.partials.tabs')
        <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            @include('reports.partials.period-links', ['routeName' => 'reports.sellers.index'])
            <form method="GET" action="{{ route('reports.sellers.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                <input type="hidden" name="period" value="custom">
                <div><x-input-label for="date_from" value="Fecha desde" /><x-text-input id="date_from" name="date_from" type="date" class="mt-1.5 block w-full" :value="$range['dateFrom']" required /></div>
                <div><x-input-label for="date_to" value="Fecha hasta" /><x-text-input id="date_to" name="date_to" type="date" class="mt-1.5 block w-full" :value="$range['dateTo']" required /></div>
                <div><x-input-label for="sort" value="Ordenar por" /><select id="sort" name="sort" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm focus:border-tik-red focus:ring-tik-red"><option value="name" @selected(($filters['sort'] ?? 'name') === 'name')>Nombre</option><option value="packages" @selected(($filters['sort'] ?? '') === 'packages')>Cantidad de paquetes</option><option value="amount" @selected(($filters['sort'] ?? '') === 'amount')>Monto asociado</option></select></div>
                <div><x-input-label for="direction" value="Dirección" /><select id="direction" name="direction" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm focus:border-tik-red focus:ring-tik-red"><option value="asc" @selected(($filters['direction'] ?? 'asc') === 'asc')>Ascendente</option><option value="desc" @selected(($filters['direction'] ?? '') === 'desc')>Descendente</option></select></div>
                <button class="h-10 rounded-lg bg-tik-ink px-5 text-sm font-semibold text-white hover:bg-black">Aplicar</button>
            </form>
            <x-input-error :messages="$errors->all()" />
        </section>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">El monto mostrado corresponde al almacenaje asociado a los paquetes. No representa una comisión calculada.</div>
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            @if ($sellers->isEmpty())<div class="px-6 py-14 text-center"><p class="font-semibold text-tik-ink">No hay vendedores con actividad en el período.</p></div>@else
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-tik-gray"><tr>@foreach (['Nombre', 'Negocio', 'Total', 'Pendientes', 'Entregados', 'Anulados', 'Monto asociado', 'Detalle'] as $heading)<th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-gray-100">
                    @foreach ($sellers as $seller)
                        @php $detailParameters = ['seller' => $seller, 'period' => $range['period']->value, 'date_from' => $range['period'] === \App\Enums\ReportPeriod::Custom ? $range['dateFrom'] : null, 'date_to' => $range['period'] === \App\Enums\ReportPeriod::Custom ? $range['dateTo'] : null]; @endphp
                        <tr class="hover:bg-red-50/40"><td class="px-5 py-4 text-sm font-semibold text-tik-ink">{{ $seller->name }}</td><td class="px-5 py-4 text-sm text-gray-600">{{ $seller->business_name ?: '—' }}</td><td class="px-5 py-4 text-sm font-semibold">{{ $seller->total_packages }}</td><td class="px-5 py-4 text-sm">{{ $seller->pending_packages }}</td><td class="px-5 py-4 text-sm">{{ $seller->delivered_packages }}</td><td class="px-5 py-4 text-sm">{{ $seller->cancelled_packages }}</td><td class="whitespace-nowrap px-5 py-4 text-sm font-semibold">Bs {{ number_format((float) ($seller->storage_amount ?? 0), 2) }}</td><td class="px-5 py-4"><a href="{{ route('reports.sellers.show', array_filter($detailParameters, fn ($value) => $value !== null)) }}" class="text-sm font-semibold text-tik-red-dark hover:text-tik-red">Ver paquetes</a></td></tr>
                    @endforeach
                </tbody></table></div>
            @endif
        </section>
        {{ $sellers->links() }}
    </div></div>
</x-app-layout>
