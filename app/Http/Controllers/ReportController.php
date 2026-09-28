<?php

namespace App\Http\Controllers;

use App\Actions\Reports\GetReportSummaryAction;
use App\Actions\Reports\ResolveReportDateRangeAction;
use App\Enums\ReportPeriod;
use App\Http\Requests\Reports\ReportSummaryRequest;
use App\Models\User;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(
        ReportSummaryRequest $request,
        ResolveReportDateRangeAction $resolveDateRange,
        GetReportSummaryAction $getSummary,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $range = $resolveDateRange->execute($request->validated());

        return view('reports.index', [
            'metrics' => $getSummary->execute($user, $range['start'], $range['end']),
            'range' => $range,
            'periods' => ReportPeriod::cases(),
        ]);
    }
}
