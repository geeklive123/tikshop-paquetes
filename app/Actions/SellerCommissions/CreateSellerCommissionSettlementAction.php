<?php

namespace App\Actions\SellerCommissions;

use App\Models\Seller;
use App\Models\SellerCommissionSettlement;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSellerCommissionSettlementAction
{
    public function __construct(private CalculateSellerCommissionAction $calculateCommission) {}

    /**
     * @param  array{date_from: string, date_to: string, notes?: string|null}  $data
     */
    public function execute(User $user, Seller $seller, array $data): SellerCommissionSettlement
    {
        try {
            return DB::transaction(function () use ($user, $seller, $data): SellerCommissionSettlement {
                $lockedSeller = Seller::query()
                    ->whereKey($seller->id)
                    ->where('company_id', $user->company_id)
                    ->lockForUpdate()
                    ->first();

                if ($lockedSeller === null) {
                    throw (new ModelNotFoundException)->setModel(Seller::class);
                }

                $calculation = $this->calculateCommission->execute(
                    $user,
                    $lockedSeller,
                    $data['date_from'],
                    $data['date_to'],
                    lockForUpdate: true,
                );

                if ($calculation['payablePackages']->isEmpty()) {
                    throw ValidationException::withMessages([
                        'date_from' => 'No existen paquetes entregados y pagables en el rango seleccionado.',
                    ]);
                }

                $settlement = SellerCommissionSettlement::query()->create([
                    'company_id' => $user->company_id,
                    'seller_id' => $lockedSeller->id,
                    'date_from' => $calculation['dateFrom'],
                    'date_to' => $calculation['dateTo'],
                    'delivered_packages_count' => $calculation['payablePackages']->count(),
                    'commission_total' => $calculation['commissionTotal'],
                    'paid_at' => now(),
                    'paid_by' => $user->id,
                    'notes' => filled($data['notes'] ?? null) ? $data['notes'] : null,
                ]);

                foreach ($calculation['payablePackages'] as $package) {
                    $settlement->items()->create([
                        'package_id' => $package->id,
                        'category_name_snapshot' => $package->category->name,
                        'commission_rate' => $package->category->commission_rate,
                        'commission_amount' => $package->category->commission_rate,
                    ]);
                }

                return $settlement;
            }, 5);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'date_from' => 'Uno o más paquetes ya fueron incluidos en otro pago de comisión.',
            ]);
        }
    }
}
