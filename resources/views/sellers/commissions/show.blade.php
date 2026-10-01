<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="text-sm font-semibold text-tik-red">Detalle de liquidación</p><h1 class="mt-1 font-mono text-xl font-bold text-tik-ink sm:text-2xl">{{ $settlement->ulid }}</h1></div>
            <a href="{{ route('sellers.commissions.index', $seller) }}" class="text-sm font-semibold text-gray-600 hover:text-tik-red-dark">Volver a comisiones</a>
        </div>
    </x-slot>

    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @if (session('status'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('status') }}</div>@endif
        <section class="grid grid-cols-1 gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-4 sm:p-6">
            <div><p class="text-sm text-gray-500">Vendedor</p><p class="mt-1 font-bold text-tik-ink">{{ $seller->name }}</p></div>
            <div><p class="text-sm text-gray-500">Período</p><p class="mt-1 font-bold text-tik-ink">{{ $settlement->date_from->format('d/m/Y') }} – {{ $settlement->date_to->format('d/m/Y') }}</p></div>
            <div><p class="text-sm text-gray-500">Pagado</p><p class="mt-1 font-bold text-tik-ink">{{ $settlement->paid_at?->format('d/m/Y H:i') }} por {{ $settlement->paidBy?->name ?? 'Usuario eliminado' }}</p></div>
            <div><p class="text-sm text-gray-500">Total</p><p class="mt-1 text-2xl font-bold text-tik-red-dark">Bs {{ number_format((float) $settlement->commission_total, 2) }}</p></div>
            @if ($settlement->notes)<div class="sm:col-span-2 lg:col-span-4"><p class="text-sm text-gray-500">Notas</p><p class="mt-1 whitespace-pre-line text-sm text-tik-ink">{{ $settlement->notes }}</p></div>@endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4"><h2 class="font-bold text-tik-ink">Paquetes pagados ({{ $settlement->delivered_packages_count }})</h2></div>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-tik-gray"><tr>@foreach (['Tracking', 'Destinatario', 'Entregado', 'Categoría (snapshot)', 'Tarifa', 'Comisión'] as $heading)<th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($settlement->items as $item)
                        <tr><td class="px-5 py-4"><a href="{{ route('packages.show', $item->package) }}" class="font-mono text-sm font-bold text-tik-red-dark">{{ $item->package->tracking_code }}</a></td><td class="px-5 py-4 text-sm">{{ $item->package->recipient_name }}</td><td class="whitespace-nowrap px-5 py-4 text-sm">{{ $item->package->delivered_at?->format('d/m/Y H:i') }}</td><td class="px-5 py-4 text-sm font-semibold">{{ $item->category_name_snapshot }}</td><td class="whitespace-nowrap px-5 py-4 text-sm">Bs {{ number_format((float) $item->commission_rate, 2) }}</td><td class="whitespace-nowrap px-5 py-4 text-sm font-bold">Bs {{ number_format((float) $item->commission_amount, 2) }}</td></tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    </div></div>
</x-app-layout>
