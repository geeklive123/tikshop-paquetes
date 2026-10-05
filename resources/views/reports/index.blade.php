<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-tik-red">Información operativa</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Reportes</h1>
            <p class="mt-1 text-sm text-gray-500">Datos registrados entre {{ $range['start']->format('d/m/Y') }} y {{ $range['end']->format('d/m/Y') }}.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('reports.partials.tabs')

            <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
                @include('reports.partials.period-links', ['routeName' => 'reports.index'])
                <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                    <input type="hidden" name="period" value="custom">
                    <div><x-input-label for="date_from" value="Fecha desde" /><x-text-input id="date_from" name="date_from" type="date" class="mt-1.5 block w-full" :value="$range['dateFrom']" required /></div>
                    <div><x-input-label for="date_to" value="Fecha hasta" /><x-text-input id="date_to" name="date_to" type="date" class="mt-1.5 block w-full" :value="$range['dateTo']" required /></div>
                    <button class="h-10 rounded-lg bg-tik-ink px-5 text-sm font-semibold text-white hover:bg-black">Aplicar rango</button>
                </form>
                <x-input-error :messages="$errors->all()" />
            </section>

            @php
                $cards = [
                    ['label' => 'Paquetes recibidos', 'value' => $metrics['received']],
                    ['label' => 'Pendientes', 'value' => $metrics['pending']],
                    ['label' => 'Entregados', 'value' => $metrics['delivered']],
                    ['label' => 'Anulados', 'value' => $metrics['cancelled']],
                    ['label' => 'Precio base asociado', 'value' => 'Bs '.number_format($metrics['baseStorageAmount'], 2)],
                    ['label' => 'Recargo asociado', 'value' => 'Bs '.number_format($metrics['storageSurchargeAmount'], 2)],
                    ['label' => 'Total de almacenaje asociado', 'value' => 'Bs '.number_format($metrics['storageAmount'], 2)],
                    ['label' => 'Vendedores con actividad', 'value' => $metrics['activeSellers']],
                ];
            @endphp
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Indicadores del período">
                @foreach ($cards as $card)
                    <article class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="absolute inset-y-0 left-0 w-1 bg-tik-red"></div>
                        <p class="text-sm font-medium text-gray-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-tik-ink">{{ $card['value'] }}</p>
                    </article>
                @endforeach
            </section>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                Los montos suman precio base y recargos actuales o finales de paquetes no anulados recibidos en el período. Para entregados se usa el total histórico congelado; para pendientes, el total vigente. No representan dinero cobrado, utilidad ni comisión.
            </div>
        </div>
    </div>
</x-app-layout>
