<?php

namespace App\Actions\PackageCategories;

use App\Models\PackageCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdatePackageCategoryAction
{
    /**
     * @param  array{name: string, code_prefix: string, code_start: int, code_end: int, price: string, color?: string|null, active: bool}  $data
     */
    public function execute(User $user, PackageCategory $packageCategory, array $data): PackageCategory
    {
        return DB::transaction(function () use ($user, $packageCategory, $data): PackageCategory {
            $category = PackageCategory::query()
                ->whereKey($packageCategory->getKey())
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            $category->update($data);

            return $category;
        });
    }
}
