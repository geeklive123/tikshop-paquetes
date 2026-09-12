<?php

namespace App\Actions\PackageCategories;

use App\Models\PackageCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreatePackageCategoryAction
{
    /**
     * @param  array{name: string, code_prefix: string, code_start: int, code_end: int, price: string, color?: string|null, active: bool}  $data
     */
    public function execute(User $user, array $data): PackageCategory
    {
        return DB::transaction(fn (): PackageCategory => $user->company->packageCategories()->create($data));
    }
}
