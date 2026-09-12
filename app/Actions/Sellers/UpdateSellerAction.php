<?php

namespace App\Actions\Sellers;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateSellerAction
{
    /** @param array{name?: string, business_name?: string|null, phone?: string, document_type?: string|null, document_number?: string|null, address?: string|null, notes?: string|null, active?: bool} $data */
    public function execute(User $actor, Seller $seller, array $data): Seller
    {
        return DB::transaction(function () use ($actor, $seller, $data): Seller {
            $seller = Seller::query()
                ->whereKey($seller->getKey())
                ->where('company_id', $actor->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            $seller->update($data);

            return $seller;
        }, 5);
    }
}
