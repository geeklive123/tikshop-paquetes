<?php

namespace App\Actions\Reports;

use App\Enums\ReportPeriod;
use Carbon\CarbonImmutable;

class ResolveReportDateRangeAction
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{start: CarbonImmutable, end: CarbonImmutable, period: ReportPeriod, dateFrom: string, dateTo: string}
     */
    public function execute(array $filters): array
    {
        $timezone = config('app.timezone');
        $now = CarbonImmutable::now($timezone);
        $period = ReportPeriod::from((string) $filters['period']);

        [$start, $end] = match ($period) {
            ReportPeriod::Today => [$now->startOfDay(), $now->endOfDay()],
            ReportPeriod::ThisWeek => [$now->startOfWeek(), $now->endOfWeek()],
            ReportPeriod::ThisMonth => [$now->startOfMonth(), $now->endOfMonth()],
            ReportPeriod::Custom => [
                CarbonImmutable::parse((string) $filters['date_from'], $timezone)->startOfDay(),
                CarbonImmutable::parse((string) $filters['date_to'], $timezone)->endOfDay(),
            ],
        };

        return [
            'start' => $start,
            'end' => $end,
            'period' => $period,
            'dateFrom' => $start->toDateString(),
            'dateTo' => $end->toDateString(),
        ];
    }
}
