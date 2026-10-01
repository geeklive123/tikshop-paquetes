<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone', 'active'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * @return HasMany<Branch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Package, $this> */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    /** @return HasMany<PackageCategory, $this> */
    public function packageCategories(): HasMany
    {
        return $this->hasMany(PackageCategory::class);
    }

    /** @return HasMany<Seller, $this> */
    public function sellers(): HasMany
    {
        return $this->hasMany(Seller::class);
    }

    /** @return HasMany<Printer, $this> */
    public function printers(): HasMany
    {
        return $this->hasMany(Printer::class);
    }

    /** @return HasMany<PrinterAgent, $this> */
    public function printerAgents(): HasMany
    {
        return $this->hasMany(PrinterAgent::class);
    }

    /** @return HasMany<PrintJob, $this> */
    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class);
    }

    /** @return HasMany<SellerCommissionSettlement, $this> */
    public function sellerCommissionSettlements(): HasMany
    {
        return $this->hasMany(SellerCommissionSettlement::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
