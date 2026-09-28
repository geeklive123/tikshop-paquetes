@php
    $preservedParameters = request()->except(['period', 'date_from', 'date_to', 'page']);
    $requiredParameters = $routeParameters ?? [];
@endphp
<div class="flex flex-wrap gap-2" aria-label="Períodos rápidos">
    @foreach ([\App\Enums\ReportPeriod::Today, \App\Enums\ReportPeriod::ThisWeek, \App\Enums\ReportPeriod::ThisMonth] as $periodOption)
        <a href="{{ route($routeName, [...$requiredParameters, ...$preservedParameters, 'period' => $periodOption->value]) }}" @class([
            'rounded-lg px-3 py-2 text-sm font-semibold transition',
            'bg-tik-red text-white' => $range['period'] === $periodOption,
            'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50' => $range['period'] !== $periodOption,
        ])>{{ $periodOption->label() }}</a>
    @endforeach
</div>
