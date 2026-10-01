<?php

namespace App\Models;

use Database\Factories\SellerCommissionSettlementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'seller_id',
    'date_from',
    'date_to',
    'delivered_packages_count',
    'commission_total',
    'paid_at',
    'paid_by',
    'notes',
])]
class SellerCommissionSettlement extends Model
{
    /** @use HasFactory<SellerCommissionSettlementFactory> */
    use HasFactory, HasUlids;

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** @return BelongsTo<User, $this> */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /** @return HasMany<SellerCommissionItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SellerCommissionItem::class, 'settlement_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'commission_total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }
}
