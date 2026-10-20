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

            <section class="space-y-3" aria-label="Indicadores operativos del período">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-600">Operación</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ([['Paquetes recibidos', $metrics['received']], ['Entregados', $metrics['delivered']], ['Anulados', $metrics['cancelled']], ['Vendedores con actividad', $metrics['activeSellers']]] as [$label, $value])
                        <article class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"><div class="absolute inset-y-0 left-0 w-1 bg-tik-red"></div><p class="text-sm font-medium text-gray-500">{{ $label }}</p><p class="mt-3 text-3xl font-bold tracking-tight text-tik-ink">{{ $value }}</p></article>
                    @endforeach
                </div>
            </section>

            <section class="space-y-3" aria-label="Indicadores financieros">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-600">Pagos</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <article class="rounded-2xl border border-green-200 bg-green-50 p-5 shadow-sm"><p class="text-sm font-medium text-green-800">Paquetes pagados en el período</p><p class="mt-3 text-3xl font-bold text-green-900">{{ $metrics['paid'] }}</p></article>
                    <article class="rounded-2xl border border-green-200 bg-green-50 p-5 shadow-sm"><p class="text-sm font-medium text-green-800">Monto pagado en el período</p><p class="mt-3 text-3xl font-bold text-green-900">Bs {{ number_format($metrics['paidAmount'], 2) }}</p></article>
                </div>
                <h2 class="pt-3 text-sm font-bold uppercase tracking-wider text-gray-600">Pendiente actual</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <article class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm"><p class="text-sm font-medium text-red-800">Paquetes por pagar</p><p class="mt-3 text-3xl font-bold text-red-900">{{ $metrics['pending'] }}</p></article>
                    <article class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm"><p class="text-sm font-medium text-red-800">Monto pendiente con recargo vigente</p><p class="mt-3 text-3xl font-bold text-red-900">Bs {{ number_format($metrics['pendingAmount'], 2) }}</p></article>
                </div>
                <h2 class="pt-3 text-sm font-bold uppercase tracking-wider text-gray-600">Histórico</h2>
                <article class="rounded-2xl border border-gray-200 bg-gray-50 p-5 shadow-sm"><p class="text-sm font-medium text-gray-700">Paquetes sin seguimiento financiero</p><p class="mt-3 text-3xl font-bold text-gray-800">{{ $metrics['legacy'] }}</p><p class="mt-2 text-sm text-gray-600">No cuentan como deuda ni como pago.</p></article>
            </section>
        </div>
    </div>
</x-app-layout>
