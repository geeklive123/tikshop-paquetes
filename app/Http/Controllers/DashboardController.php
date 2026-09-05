<?php

namespace App\Http\Controllers;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        Gate::authorize('viewAny', Package::class);

        /** @var User $user */
        $user = $request->user();
        $companyPackages = Package::query()->forCompany($user->company);

        $metrics = [
            'received_today' => (clone $companyPackages)
                ->whereDate('received_at', today())
                ->count(),
            'pending' => (clone $companyPackages)
                ->where('status', PackageStatus::Received)
                ->count(),
            'ready_for_pickup' => (clone $companyPackages)
                ->where('status', PackageStatus::ReadyForPickup)
                ->count(),
            'delivered_today' => (clone $companyPackages)
                ->where('status', PackageStatus::Delivered)
                ->whereDate('delivered_at', today())
                ->count(),
        ];

        $latestPackages = (clone $companyPackages)
            ->latest('received_at')
            ->latest('id')
            ->limit(5)
            ->get(['id', 'ulid', 'tracking_code', 'recipient_name', 'status', 'received_at']);

        return view('dashboard', compact('metrics', 'latestPackages'));
    }
}
