<?php

namespace App\Models;

use Database\Factories\SellerCommissionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'settlement_id',
    'package_id',
    'category_name_snapshot',
    'commission_rate',
    'commission_amount',
])]
class SellerCommissionItem extends Model
{
    /** @use HasFactory<SellerCommissionItemFactory> */
    use HasFactory;

    /** @return BelongsTo<SellerCommissionSettlement, $this> */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(SellerCommissionSettlement::class, 'settlement_id');
    }

    /** @return BelongsTo<Package, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }
}
