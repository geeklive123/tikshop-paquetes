<?php

namespace App\Actions\Sellers;

use App\Enums\UserRole;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateSellerAction
{
    /** @param array{name: string, business_name?: string|null, phone: string, document_type?: string|null, document_number?: string|null, address?: string|null, notes?: string|null, active: bool} $data */
    public function execute(User $actor, array $data): Seller
    {
        if ($actor->role === UserRole::Operator) {
            $data['active'] = true;
        }

        return DB::transaction(fn (): Seller => $actor->company->sellers()->create($data));
    }
}
