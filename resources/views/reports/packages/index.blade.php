<x-app-layout>
    <x-slot name="header">
        <div><p class="text-sm font-semibold text-tik-red">Reportes</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Paquetes</h1><p class="mt-1 text-sm text-gray-500">Consulta operativa paginada por fecha de recepción.</p></div>
    </x-slot>

    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @include('reports.partials.tabs')
        <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            @include('reports.partials.period-links', ['routeName' => 'reports.packages.index'])
            <form method="GET" action="{{ route('reports.packages.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <input type="hidden" name="period" value="custom">
                <div><x-input-label for="date_from" value="Fecha desde" /><x-text-input id="date_from" name="date_from" type="date" class="mt-1.5 block w-full" :value="$range['dateFrom']" required /></div>
                <div><x-input-label for="date_to" value="Fecha hasta" /><x-text-input id="date_to" name="date_to" type="date" class="mt-1.5 block w-full" :value="$range['dateTo']" required /></div>
                <div><x-input-label for="status" value="Estado" /><select id="status" name="status" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm focus:border-tik-red focus:ring-tik-red"><option value="">Todos</option>@foreach ($statuses as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
                <div><x-input-label for="seller_id" value="Vendedor" /><select id="seller_id" name="seller_id" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm focus:border-tik-red focus:ring-tik-red"><option value="">Todos</option>@foreach ($sellers as $seller)<option value="{{ $seller->id }}" @selected((int) ($filters['seller_id'] ?? 0) === $seller->id)>{{ $seller->business_name ?: $seller->name }}</option>@endforeach</select></div>
                <div><x-input-label for="category_id" value="Categoría" /><select id="category_id" name="category_id" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm focus:border-tik-red focus:ring-tik-red"><option value="">Todas</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div><x-input-label for="branch_id" value="Sucursal" /><select id="branch_id" name="branch_id" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm focus:border-tik-red focus:ring-tik-red"><option value="">Todas</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected((int) ($filters['branch_id'] ?? 0) === $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
                <div><x-input-label for="tracking_code" value="Tracking" /><x-text-input id="tracking_code" name="tracking_code" class="mt-1.5 block w-full" :value="$filters['tracking_code'] ?? ''" placeholder="Código de seguimiento" /></div>
                <div><x-input-label for="recipient" value="Destinatario" /><x-text-input id="recipient" name="recipient" class="mt-1.5 block w-full" :value="$filters['recipient'] ?? ''" placeholder="Nombre del destinatario" /></div>
                <div class="flex gap-3 sm:col-span-2 xl:col-span-4"><button class="rounded-lg bg-tik-ink px-5 py-2.5 text-sm font-semibold text-white hover:bg-black">Filtrar</button><a href="{{ route('reports.packages.index') }}" class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">Limpiar</a></div>
            </form>
            <x-input-error :messages="$errors->all()" />
        </section>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            @if ($packages->isEmpty())<div class="px-6 py-14 text-center"><p class="font-semibold text-tik-ink">No se encontraron paquetes.</p></div>@else
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-tik-gray"><tr>@foreach (['Tracking', 'Vendedor', 'Destinatario', 'Ubicación', 'Categoría', 'Precio base', 'Recargo', 'Total almacenaje', 'Estado', 'Recepción', 'Entrega'] as $heading)<th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-gray-100">
                    @foreach ($packages as $package)<tr class="hover:bg-red-50/40"><td class="whitespace-nowrap px-4 py-4 font-mono text-sm font-bold text-tik-red-dark">{{ $package->tracking_code }}</td><td class="px-4 py-4 text-sm text-tik-ink">{{ $package->seller?->business_name ?: ($package->seller?->name ?? $package->sender_name) }}</td><td class="px-4 py-4 text-sm text-tik-ink">{{ $package->recipient_name }}</td><td class="whitespace-nowrap px-4 py-4 font-mono text-sm font-semibold">{{ $package->storage_code }}</td><td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $package->category?->name ?? 'Sin categoría' }}</td><td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">Bs {{ $package->storage_amount_breakdown['baseAmount'] }}</td><td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">Bs {{ $package->storage_amount_breakdown['surchargeAmount'] }}</td><td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-tik-ink">Bs {{ $package->storage_amount_breakdown['totalAmount'] }}</td><td class="whitespace-nowrap px-4 py-4"><x-package-status-badge :status="$package->status" /></td><td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $package->received_at?->format('d/m/Y H:i') }}</td><td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $package->delivered_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>@endforeach
                </tbody></table></div>
            @endif
        </section>
        {{ $packages->links() }}
    </div></div>
</x-app-layout>
