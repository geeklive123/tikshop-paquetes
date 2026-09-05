<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-tik-red">Resumen operativo</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Actividad de {{ Auth::user()->company->name }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Métricas de paquetes">
                @php
                    $cards = [
                        ['label' => 'Recibidos hoy', 'value' => $metrics['received_today'], 'color' => 'bg-red-50 text-tik-red-dark', 'dot' => 'bg-tik-red'],
                        ['label' => 'Pendientes', 'value' => $metrics['pending'], 'color' => 'bg-tik-gray text-tik-ink', 'dot' => 'bg-tik-ink'],
                        ['label' => 'Listos para recoger', 'value' => $metrics['ready_for_pickup'], 'color' => 'bg-orange-50 text-amber-900', 'dot' => 'bg-tik-box'],
                        ['label' => 'Entregados hoy', 'value' => $metrics['delivered_today'], 'color' => 'bg-tik-ink text-white', 'dot' => 'bg-white'],
                    ];
                @endphp
                @foreach ($cards as $card)
                    <article class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="absolute inset-y-0 left-0 w-1 bg-tik-red"></div>
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-sm font-medium text-gray-500">{{ $card['label'] }}</p>
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $card['color'] }}"><span class="h-2.5 w-2.5 rounded-full {{ $card['dot'] }}"></span></span>
                        </div>
                        <p class="mt-4 text-3xl font-bold tracking-tight text-tik-ink">{{ $card['value'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-gray-200 bg-tik-gray/70 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div><h2 class="text-lg font-bold text-tik-ink">Últimos paquetes registrados</h2><p class="mt-1 text-sm text-gray-500">Los cinco ingresos más recientes.</p></div>
                    <a href="{{ route('packages.index') }}" class="text-sm font-semibold text-tik-red-dark hover:text-tik-red">Ver todos</a>
                </div>
                @if ($latestPackages->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="font-semibold text-tik-ink">Todavía no hay paquetes registrados.</p>
                        <a href="{{ route('packages.create') }}" class="mt-3 inline-flex text-sm font-semibold text-tik-red-dark hover:text-tik-red">Registrar el primero</a>
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($latestPackages as $package)
                            <a href="{{ route('packages.show', $package) }}" class="flex flex-col gap-3 px-5 py-4 transition hover:bg-red-50/50 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                <div class="min-w-0"><p class="font-mono text-sm font-bold text-tik-red-dark">{{ $package->tracking_code }}</p><p class="mt-1 truncate text-sm font-medium text-tik-ink">{{ $package->recipient_name }}</p></div>
                                <div class="flex items-center justify-between gap-4 sm:justify-end">
                                    <x-package-status-badge :status="$package->status" />
                                    <time class="whitespace-nowrap text-xs text-gray-500">{{ $package->received_at?->format('d/m/Y H:i') }}</time>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
