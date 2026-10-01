<?php

namespace App\Actions\SellerCommissions;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\Seller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class CalculateSellerCommissionAction
{
    /**
     * @return array{
     *     dateFrom: string,
     *     dateTo: string,
     *     totalPackages: int,
     *     deliveredPackages: int,
     *     unpaidDeliveredPackages: int,
     *     payablePackages: Collection<int, Package>,
     *     missingRatePackages: int,
     *     pendingPackages: int,
     *     breakdown: array<int, array{categoryName: string, quantity: int, rate: string, subtotal: string}>,
     *     commissionTotal: string
     * }
     */
    public function execute(
        User $user,
        Seller $seller,
        string $dateFrom,
        string $dateTo,
        bool $lockForUpdate = false,
    ): array {
        $timezone = (string) config('app.timezone');
        $start = CarbonImmutable::parse($dateFrom, $timezone)->startOfDay();
        $end = CarbonImmutable::parse($dateTo, $timezone)->endOfDay();

        $deliveredQuery = Package::query()
            ->where('company_id', $user->company_id)
            ->where('seller_id', $seller->id)
            ->where('status', PackageStatus::Delivered)
            ->whereBetween('delivered_at', [$start, $end]);

        $unpaidQuery = (clone $deliveredQuery)
            ->whereDoesntHave('commissionItem')
            ->orderBy('id');

        if ($lockForUpdate) {
            $unpaidQuery->lockForUpdate();
        }

        $unpaidPackages = $unpaidQuery->get();
        $categoriesQuery = PackageCategory::query()
            ->where('company_id', $user->company_id)
            ->whereIn('id', $unpaidPackages->pluck('package_category_id')->filter()->unique()->values());

        if ($lockForUpdate) {
            $categoriesQuery->lockForUpdate();
        }

        $categories = $categoriesQuery->get(['id', 'name', 'commission_rate'])->keyBy('id');
        $unpaidPackages->each(function (Package $package) use ($categories): void {
            $package->setRelation('category', $categories->get($package->package_category_id));
        });

        $payablePackages = $unpaidPackages
            ->filter(fn (Package $package): bool => $package->category?->commission_rate !== null)
            ->values();
        $breakdown = [];
        $commissionTotalInCents = 0;

        foreach ($payablePackages as $package) {
            $category = $package->category;
            $rate = (string) $category->commission_rate;
            $rateInCents = $this->decimalToCents($rate);
            $key = $category->id;

            if (! isset($breakdown[$key])) {
                $breakdown[$key] = [
                    'categoryName' => $category->name,
                    'quantity' => 0,
                    'rate' => $this->centsToDecimal($rateInCents),
                    'subtotalInCents' => 0,
                ];
            }

            $breakdown[$key]['quantity']++;
            $breakdown[$key]['subtotalInCents'] += $rateInCents;
            $commissionTotalInCents += $rateInCents;
        }

        $formattedBreakdown = array_values(array_map(
            fn (array $row): array => [
                'categoryName' => $row['categoryName'],
                'quantity' => $row['quantity'],
                'rate' => $row['rate'],
                'subtotal' => $this->centsToDecimal($row['subtotalInCents']),
            ],
            $breakdown,
        ));

        $receivedInPeriod = Package::query()
            ->where('company_id', $user->company_id)
            ->where('seller_id', $seller->id)
            ->whereBetween('received_at', [$start, $end]);

        return [
            'dateFrom' => $start->toDateString(),
            'dateTo' => $end->toDateString(),
            'totalPackages' => (clone $receivedInPeriod)->count(),
            'deliveredPackages' => (clone $deliveredQuery)->count(),
            'unpaidDeliveredPackages' => $unpaidPackages->count(),
            'payablePackages' => $payablePackages,
            'missingRatePackages' => $unpaidPackages->count() - $payablePackages->count(),
            'pendingPackages' => (clone $receivedInPeriod)
                ->whereIn('status', [PackageStatus::Received, PackageStatus::ReadyForPickup])
                ->count(),
            'breakdown' => $formattedBreakdown,
            'commissionTotal' => $this->centsToDecimal($commissionTotalInCents),
        ];
    }

    private function decimalToCents(string $amount): int
    {
        [$whole, $decimal] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }

    private function centsToDecimal(int $amountInCents): string
    {
        return sprintf('%d.%02d', intdiv($amountInCents, 100), $amountInCents % 100);
    }
}
