<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-tik-red">Reportes</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Comisiones pagadas</h1><p class="mt-1 text-sm text-gray-500">Liquidaciones pagadas entre {{ $range['start']->format('d/m/Y') }} y {{ $range['end']->format('d/m/Y') }}.</p></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @include('reports.partials.tabs')
        <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            @include('reports.partials.period-links', ['routeName' => 'reports.commissions.index'])
            <form method="GET" action="{{ route('reports.commissions.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <input type="hidden" name="period" value="custom">
                <div><x-input-label for="date_from" value="Fecha desde" /><x-text-input id="date_from" name="date_from" type="date" class="mt-1.5 block w-full" :value="$range['dateFrom']" required /></div>
                <div><x-input-label for="date_to" value="Fecha hasta" /><x-text-input id="date_to" name="date_to" type="date" class="mt-1.5 block w-full" :value="$range['dateTo']" required /></div>
                <button class="h-10 rounded-lg bg-tik-ink px-5 text-sm font-semibold text-white hover:bg-black">Aplicar rango</button>
            </form>
            <x-input-error :messages="$errors->all()" />
        </section>
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">Este reporte muestra exclusivamente comisiones pagadas; no suma ni mezcla montos de almacenaje.</div>
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            @if ($settlements->isEmpty())<p class="px-5 py-12 text-center text-sm text-gray-500">No hay comisiones pagadas en el rango.</p>@else
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-tik-gray"><tr>@foreach (['Vendedor', 'Paquetes', 'Total comisión', 'Fecha de pago', 'Pagado por', 'Detalle'] as $heading)<th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-gray-100">
                    @foreach ($settlements as $settlement)<tr><td class="px-5 py-4 text-sm font-semibold text-tik-ink">{{ $settlement->seller->name }}</td><td class="px-5 py-4 text-sm">{{ $settlement->delivered_packages_count }}</td><td class="whitespace-nowrap px-5 py-4 text-sm font-bold">Bs {{ number_format((float) $settlement->commission_total, 2) }}</td><td class="whitespace-nowrap px-5 py-4 text-sm">{{ $settlement->paid_at?->format('d/m/Y H:i') }}</td><td class="px-5 py-4 text-sm">{{ $settlement->paidBy?->name ?? 'Usuario eliminado' }}</td><td class="px-5 py-4"><a href="{{ route('sellers.commission-settlements.show', [$settlement->seller, $settlement]) }}" class="text-sm font-semibold text-tik-red-dark">Abrir</a></td></tr>@endforeach
                </tbody></table></div>
                <div class="border-t border-gray-100 px-5 py-4">{{ $settlements->links() }}</div>
            @endif
        </section>
    </div></div>
</x-app-layout>
