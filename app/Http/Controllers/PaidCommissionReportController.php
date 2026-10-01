<?php

namespace App\Http\Controllers;

use App\Actions\Reports\GetPaidCommissionReportAction;
use App\Actions\Reports\ResolveReportDateRangeAction;
use App\Http\Requests\Reports\PaidCommissionReportRequest;
use App\Models\User;
use Illuminate\View\View;

class PaidCommissionReportController extends Controller
{
    public function __invoke(
        PaidCommissionReportRequest $request,
        ResolveReportDateRangeAction $resolveDateRange,
        GetPaidCommissionReportAction $getPaidCommissions,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $range = $resolveDateRange->execute($request->validated());

        return view('reports.commissions.index', [
            'settlements' => $getPaidCommissions->execute($user, $range['start'], $range['end']),
            'range' => $range,
        ]);
    }
}
