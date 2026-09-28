<?php

namespace App\Http\Controllers;

use App\Actions\Reports\GetCancellationReportAction;
use App\Actions\Reports\ResolveReportDateRangeAction;
use App\Http\Requests\Reports\CancellationReportRequest;
use App\Models\Seller;
use App\Models\User;
use Illuminate\View\View;

class CancellationReportController extends Controller
{
    public function index(
        CancellationReportRequest $request,
        ResolveReportDateRangeAction $resolveDateRange,
        GetCancellationReportAction $getCancellations,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $filters = $request->validated();
        $range = $resolveDateRange->execute($filters);

        return view('reports.cancellations.index', [
            'packages' => $getCancellations->execute($user, $range['start'], $range['end'], $filters),
            'range' => $range,
            'filters' => $filters,
            'sellers' => Seller::query()->where('company_id', $user->company_id)->orderBy('name')->get(['id', 'name', 'business_name']),
            'users' => User::query()->where('company_id', $user->company_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
