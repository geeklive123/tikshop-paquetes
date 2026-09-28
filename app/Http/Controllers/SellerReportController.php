<?php

namespace App\Http\Controllers;

use App\Actions\Reports\GetPackageReportAction;
use App\Actions\Reports\GetSellerReportAction;
use App\Actions\Reports\ResolveReportDateRangeAction;
use App\Enums\PackageStatus;
use App\Http\Requests\Reports\SellerDetailReportRequest;
use App\Http\Requests\Reports\SellerReportRequest;
use App\Models\PackageCategory;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SellerReportController extends Controller
{
    public function index(
        SellerReportRequest $request,
        ResolveReportDateRangeAction $resolveDateRange,
        GetSellerReportAction $getSellers,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $filters = $request->validated();
        $range = $resolveDateRange->execute($filters);

        return view('reports.sellers.index', [
            'sellers' => $getSellers->execute($user, $range['start'], $range['end'], $filters),
            'range' => $range,
            'filters' => $filters,
        ]);
    }

    public function show(
        SellerDetailReportRequest $request,
        Seller $seller,
        ResolveReportDateRangeAction $resolveDateRange,
        GetPackageReportAction $getPackages,
    ): View {
        Gate::authorize('view', $seller);

        /** @var User $user */
        $user = $request->user();
        $filters = $request->validated();
        $range = $resolveDateRange->execute($filters);

        return view('reports.sellers.show', [
            'seller' => $seller,
            'packages' => $getPackages->execute($user, $range['start'], $range['end'], $filters, $seller->id),
            'range' => $range,
            'filters' => $filters,
            'statuses' => PackageStatus::cases(),
            'categories' => PackageCategory::query()->where('company_id', $user->company_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
