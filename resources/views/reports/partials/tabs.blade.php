<nav class="flex flex-wrap gap-2" aria-label="Secciones de reportes">
    @foreach ([
        ['route' => 'reports.index', 'label' => 'Resumen'],
        ['route' => 'reports.packages.index', 'label' => 'Paquetes'],
        ['route' => 'reports.sellers.index', 'label' => 'Vendedores'],
        ['route' => 'reports.cancellations.index', 'label' => 'Anulados'],
        ['route' => 'reports.commissions.index', 'label' => 'Comisiones pagadas'],
    ] as $tab)
        <a href="{{ route($tab['route']) }}" @class([
            'rounded-xl px-4 py-2 text-sm font-semibold transition',
            'bg-tik-ink text-white' => request()->routeIs($tab['route']) || ($tab['route'] === 'reports.sellers.index' && request()->routeIs('reports.sellers.show')),
            'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50' => ! request()->routeIs($tab['route']) && ! ($tab['route'] === 'reports.sellers.index' && request()->routeIs('reports.sellers.show')),
        ])>{{ $tab['label'] }}</a>
    @endforeach
</nav>
