<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-tik-red">Reportes · Vendedores</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">{{ $seller->name }}</h1><p class="mt-1 text-sm text-gray-500">{{ $seller->business_name ?: 'Sin negocio registrado' }} · Paquetes recibidos en el rango seleccionado.</p></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @include('reports.partials.tabs')
        <div><a href="{{ route('reports.sellers.index', ['period' => $range['period']->value, 'date_from' => $range['period'] === \App\Enums\ReportPeriod::Custom ? $range['dateFrom'] : null, 'date_to' => $range['period'] === \App\Enums\ReportPeriod::Custom ? $range['dateTo'] : null]) }}" class="text-sm font-semibold text-tik-red-dark hover:text-tik-red">← Volver al reporte de vendedores</a></div>
        <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            @include('reports.partials.period-links', ['routeName' => 'reports.sellers.show', 'routeParameters' => ['seller' => $seller]])
            <form method="GET" action="{{ route('reports.sellers.show', $seller) }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <input type="hidden" name="period" value="custom">
                <div><x-input-label for="date_from" value="Fecha desde" /><x-text-input id="date_from" name="date_from" type="date" class="mt-1.5 block w-full" :value="$range['dateFrom']" required /></div>
                <div><x-input-label for="date_to" value="Fecha hasta" /><x-text-input id="date_to" name="date_to" type="date" class="mt-1.5 block w-full" :value="$range['dateTo']" required /></div>
                <div><x-input-label for="status" value="Estado" /><select id="status" name="status" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm focus:border-tik-red focus:ring-tik-red"><option value="">Todos</option>@foreach ($statuses as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
                <div><x-input-label for="category_id" value="Categoría" /><select id="category_id" name="category_id" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm focus:border-tik-red focus:ring-tik-red"><option value="">Todas</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div><x-input-label for="tracking_code" value="Tracking" /><x-text-input id="tracking_code" name="tracking_code" class="mt-1.5 block w-full" :value="$filters['tracking_code'] ?? ''" /></div>
                <div><x-input-label for="recipient" value="Destinatario" /><x-text-input id="recipient" name="recipient" class="mt-1.5 block w-full" :value="$filters['recipient'] ?? ''" /></div>
                <div class="flex gap-3 sm:col-span-2 lg:col-span-3"><button class="rounded-lg bg-tik-ink px-5 py-2.5 text-sm font-semibold text-white hover:bg-black">Filtrar</button></div>
            </form>
            <x-input-error :messages="$errors->all()" />
        </section>
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            @if ($packages->isEmpty())<div class="px-6 py-14 text-center"><p class="font-semibold text-tik-ink">No se encontraron paquetes.</p></div>@else
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-tik-gray"><tr>@foreach (['Tracking', 'Destinatario', 'Ubicación', 'Categoría', 'Precio', 'Estado', 'Recepción', 'Entrega'] as $heading)<th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-gray-100">
                    @foreach ($packages as $package)<tr class="hover:bg-red-50/40"><td class="whitespace-nowrap px-5 py-4 font-mono text-sm font-bold text-tik-red-dark">{{ $package->tracking_code }}</td><td class="px-5 py-4 text-sm text-tik-ink">{{ $package->recipient_name }}</td><td class="whitespace-nowrap px-5 py-4 font-mono text-sm font-semibold">{{ $package->storage_code }}</td><td class="px-5 py-4 text-sm text-gray-600">{{ $package->category?->name ?? 'Sin categoría' }}</td><td class="whitespace-nowrap px-5 py-4 text-sm">Bs {{ number_format((float) $package->storage_price, 2) }}</td><td class="whitespace-nowrap px-5 py-4"><x-package-status-badge :status="$package->status" /></td><td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">{{ $package->received_at?->format('d/m/Y H:i') }}</td><td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">{{ $package->delivered_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>@endforeach
                </tbody></table></div>
            @endif
        </section>
        {{ $packages->links() }}
    </div></div>
</x-app-layout>
