<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-tik-red">Reportes</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Paquetes anulados</h1><p class="mt-1 text-sm text-gray-500">Auditoría por fecha de anulación.</p></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @include('reports.partials.tabs')
        <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            @include('reports.partials.period-links', ['routeName' => 'reports.cancellations.index'])
            <form method="GET" action="{{ route('reports.cancellations.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                <input type="hidden" name="period" value="custom">
                <div><x-input-label for="date_from" value="Fecha desde" /><x-text-input id="date_from" name="date_from" type="date" class="mt-1.5 block w-full" :value="$range['dateFrom']" required /></div>
                <div><x-input-label for="date_to" value="Fecha hasta" /><x-text-input id="date_to" name="date_to" type="date" class="mt-1.5 block w-full" :value="$range['dateTo']" required /></div>
                <div><x-input-label for="seller_id" value="Vendedor" /><select id="seller_id" name="seller_id" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm focus:border-tik-red focus:ring-tik-red"><option value="">Todos</option>@foreach ($sellers as $seller)<option value="{{ $seller->id }}" @selected((int) ($filters['seller_id'] ?? 0) === $seller->id)>{{ $seller->business_name ?: $seller->name }}</option>@endforeach</select></div>
                <div><x-input-label for="cancelled_by" value="Anulado por" /><select id="cancelled_by" name="cancelled_by" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm focus:border-tik-red focus:ring-tik-red"><option value="">Todos</option>@foreach ($users as $cancellingUser)<option value="{{ $cancellingUser->id }}" @selected((int) ($filters['cancelled_by'] ?? 0) === $cancellingUser->id)>{{ $cancellingUser->name }}</option>@endforeach</select></div>
                <button class="h-10 rounded-lg bg-tik-ink px-5 text-sm font-semibold text-white hover:bg-black">Filtrar</button>
            </form>
            <x-input-error :messages="$errors->all()" />
        </section>
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            @if ($packages->isEmpty())<div class="px-6 py-14 text-center"><p class="font-semibold text-tik-ink">No se encontraron anulaciones.</p></div>@else
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-tik-gray"><tr>@foreach (['Tracking', 'Vendedor', 'Destinatario', 'Motivo', 'Anulado por', 'Fecha anulación', 'Recepción'] as $heading)<th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-gray-100">
                    @foreach ($packages as $package)<tr class="hover:bg-red-50/40"><td class="whitespace-nowrap px-5 py-4 font-mono text-sm font-bold text-tik-red-dark">{{ $package->tracking_code }}</td><td class="px-5 py-4 text-sm text-tik-ink">{{ $package->seller?->business_name ?: ($package->seller?->name ?? $package->sender_name) }}</td><td class="px-5 py-4 text-sm text-tik-ink">{{ $package->recipient_name }}</td><td class="min-w-64 px-5 py-4 text-sm text-gray-600">{{ $package->cancellation_reason ?: 'Sin motivo histórico registrado' }}</td><td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">{{ $package->cancelledBy?->name ?? 'Sin usuario histórico' }}</td><td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">{{ $package->cancelled_at?->format('d/m/Y H:i') ?? '—' }}</td><td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">{{ $package->received_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>@endforeach
                </tbody></table></div>
            @endif
        </section>
        {{ $packages->links() }}
    </div></div>
</x-app-layout>
