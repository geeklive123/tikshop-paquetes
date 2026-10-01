<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-tik-red">Comisiones del vendedor</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">{{ $seller->name }}</h1>
            </div>
            <a href="{{ route('sellers.show', $seller) }}" class="text-sm font-semibold text-gray-600 hover:text-tik-red-dark">Volver al vendedor</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('status') }}</div>
            @endif

            @can('calculateCommission', $seller)
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-5">
                        <h2 class="font-bold text-tik-ink">Calcular comisión</h2>
                        <p class="mt-1 text-sm text-gray-500">El rango usa la fecha de entrega en America/La_Paz.</p>
                    </div>
                    <form method="GET" action="{{ route('sellers.commissions.calculate', $seller) }}" class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                        <div>
                            <x-input-label for="date_from" value="Desde" />
                            <x-text-input id="date_from" name="date_from" type="date" class="mt-1.5 block w-full" :value="$dateFrom" required />
                            <x-input-error :messages="$errors->get('date_from')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="date_to" value="Hasta" />
                            <x-text-input id="date_to" name="date_to" type="date" class="mt-1.5 block w-full" :value="$dateTo" required />
                            <x-input-error :messages="$errors->get('date_to')" class="mt-2" />
                        </div>
                        <button class="h-10 rounded-lg bg-tik-ink px-5 text-sm font-semibold text-white hover:bg-black">Calcular</button>
                    </form>
                </section>
            @endcan

            @if ($calculation !== null)
                <section class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <div>
                        <h2 class="font-bold text-tik-ink">Resultado del {{ \Carbon\CarbonImmutable::parse($calculation['dateFrom'])->format('d/m/Y') }} al {{ \Carbon\CarbonImmutable::parse($calculation['dateTo'])->format('d/m/Y') }}</h2>
                        <p class="mt-1 text-xs text-gray-500">Los entregados usan delivered_at; los registrados y pendientes usan received_at para el contexto operativo.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl bg-gray-50 p-4"><p class="text-sm text-gray-500">Total registrados</p><p class="mt-1 text-2xl font-bold text-tik-ink">{{ $calculation['totalPackages'] }}</p></div>
                        <div class="rounded-xl bg-green-50 p-4"><p class="text-sm text-green-700">Entregados</p><p class="mt-1 text-2xl font-bold text-green-800">{{ $calculation['deliveredPackages'] }}</p></div>
                        <div class="rounded-xl bg-blue-50 p-4"><p class="text-sm text-blue-700">Entregados no pagados</p><p class="mt-1 text-2xl font-bold text-blue-800">{{ $calculation['unpaidDeliveredPackages'] }}</p></div>
                        <div class="rounded-xl bg-amber-50 p-4"><p class="text-sm text-amber-700">Pendientes/no entregados</p><p class="mt-1 text-2xl font-bold text-amber-800">{{ $calculation['pendingPackages'] }}</p></div>
                    </div>

                    @if ($calculation['missingRatePackages'] > 0)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            {{ $calculation['missingRatePackages'] }} paquete(s) entregado(s) no tienen una tarifa de comisión configurada y no se incluirán en este pago.
                            @can('viewAny', \App\Models\PackageCategory::class)
                                <a href="{{ route('package-categories.index') }}" class="font-semibold underline">Configurar categorías</a>
                            @endcan
                        </div>
                    @endif

                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-tik-gray"><tr>@foreach (['Categoría', 'Cantidad', 'Tarifa', 'Subtotal'] as $heading)<th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $heading }}</th>@endforeach</tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($calculation['breakdown'] as $row)
                                    <tr><td class="px-5 py-4 text-sm font-semibold text-tik-ink">{{ $row['categoryName'] }}</td><td class="px-5 py-4 text-sm">{{ $row['quantity'] }}</td><td class="whitespace-nowrap px-5 py-4 text-sm">Bs {{ $row['rate'] }}</td><td class="whitespace-nowrap px-5 py-4 text-sm font-bold">Bs {{ $row['subtotal'] }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500">No hay paquetes pagables en este rango.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-gray-50"><tr><th colspan="3" class="px-5 py-4 text-right text-sm font-bold text-tik-ink">TOTAL</th><td class="px-5 py-4 text-lg font-bold text-tik-red-dark">Bs {{ $calculation['commissionTotal'] }}</td></tr></tfoot>
                        </table>
                    </div>

                    @if ($calculation['payablePackages']->isNotEmpty())
                        <form method="POST" action="{{ route('sellers.commission-settlements.store', $seller) }}" x-data="{ submitting: false }" @submit="submitting = true" class="space-y-4 border-t border-gray-100 pt-5">
                            @csrf
                            <input type="hidden" name="date_from" value="{{ $calculation['dateFrom'] }}">
                            <input type="hidden" name="date_to" value="{{ $calculation['dateTo'] }}">
                            <div>
                                <x-input-label for="notes" value="Notas (opcional)" />
                                <textarea id="notes" name="notes" rows="3" maxlength="1000" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-tik-red focus:ring-tik-red">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                            <button type="submit" :disabled="submitting" class="inline-flex w-full items-center justify-center rounded-xl bg-tik-red px-5 py-3 text-sm font-semibold text-white hover:bg-tik-red-dark disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
                                <span x-show="!submitting">Confirmar pago de comisión</span>
                                <span x-cloak x-show="submitting">Confirmando…</span>
                            </button>
                        </form>
                    @endif
                </section>
            @endif

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4"><h2 class="font-bold text-tik-ink">Historial de liquidaciones pagadas</h2></div>
                @if ($settlements->isEmpty())
                    <p class="px-5 py-10 text-center text-sm text-gray-500">Todavía no hay pagos de comisión registrados.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-tik-gray"><tr>@foreach (['Liquidación', 'Desde', 'Hasta', 'Paquetes', 'Total', 'Fecha de pago', 'Pagado por', 'Detalle'] as $heading)<th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $heading }}</th>@endforeach</tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($settlements as $settlement)
                                    <tr class="hover:bg-red-50/40">
                                        <td class="whitespace-nowrap px-5 py-4 font-mono text-xs font-semibold text-gray-700">{{ $settlement->ulid }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm">{{ $settlement->date_from->format('d/m/Y') }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm">{{ $settlement->date_to->format('d/m/Y') }}</td>
                                        <td class="px-5 py-4 text-sm font-semibold">{{ $settlement->delivered_packages_count }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm font-bold">Bs {{ number_format((float) $settlement->commission_total, 2) }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm">{{ $settlement->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                        <td class="px-5 py-4 text-sm">{{ $settlement->paidBy?->name ?? 'Usuario eliminado' }}</td>
                                        <td class="px-5 py-4"><a href="{{ route('sellers.commission-settlements.show', [$seller, $settlement]) }}" class="text-sm font-semibold text-tik-red-dark">Abrir</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-5 py-4">{{ $settlements->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
