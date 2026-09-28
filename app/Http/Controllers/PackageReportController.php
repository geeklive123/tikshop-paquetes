<?php

namespace App\Http\Controllers;

use App\Actions\Reports\GetPackageReportAction;
use App\Actions\Reports\ResolveReportDateRangeAction;
use App\Enums\PackageStatus;
use App\Http\Requests\Reports\PackageReportRequest;
use App\Models\Branch;
use App\Models\PackageCategory;
use App\Models\Seller;
use App\Models\User;
use Illuminate\View\View;

class PackageReportController extends Controller
{
    public function index(
        PackageReportRequest $request,
        ResolveReportDateRangeAction $resolveDateRange,
        GetPackageReportAction $getPackages,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $filters = $request->validated();
        $range = $resolveDateRange->execute($filters);

        return view('reports.packages.index', [
            'packages' => $getPackages->execute($user, $range['start'], $range['end'], $filters),
            'range' => $range,
            'filters' => $filters,
            'statuses' => PackageStatus::cases(),
            'sellers' => Seller::query()->where('company_id', $user->company_id)->orderBy('name')->get(['id', 'name', 'business_name']),
            'categories' => PackageCategory::query()->where('company_id', $user->company_id)->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::query()->where('company_id', $user->company_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
