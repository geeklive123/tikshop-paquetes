<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\PackageCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class InitialSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ownerPassword = (string) config('tikshop.initial_owner.password');

        if ($ownerPassword === '') {
            throw new LogicException('Configure a secure TIKSHOP_OWNER_PASSWORD before seeding.');
        }

        DB::transaction(function () use ($ownerPassword): void {
            $company = Company::query()->firstOrCreate(
                ['name' => (string) config('tikshop.company.name')],
                ['active' => true],
            );

            Branch::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => (string) config('tikshop.main_branch.name'),
                ],
                ['active' => true],
            );

            foreach ($this->packageCategories() as $category) {
                PackageCategory::query()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'code_prefix' => $category['code_prefix'],
                    ],
                    $category,
                );
            }

            User::query()->firstOrCreate(
                ['email' => (string) config('tikshop.initial_owner.email')],
                [
                    'company_id' => $company->id,
                    'name' => (string) config('tikshop.initial_owner.name'),
                    'password' => $ownerPassword,
                    'role' => UserRole::Owner,
                    'active' => true,
                ],
            );
        });
    }

    /** @return array<int, array{name: string, code_prefix: string, code_start: int, code_end: int, price: string, color: string, active: bool}> */
    private function packageCategories(): array
    {
        return [
            ['name' => 'Pequeño', 'code_prefix' => 'P', 'code_start' => 1, 'code_end' => 5, 'price' => '2.00', 'color' => '#22C55E', 'active' => true],
            ['name' => 'Mediano', 'code_prefix' => 'M', 'code_start' => 1, 'code_end' => 10, 'price' => '3.00', 'color' => '#F59E0B', 'active' => true],
            ['name' => 'Grande', 'code_prefix' => 'G', 'code_start' => 1, 'code_end' => 5, 'price' => '5.00', 'color' => '#F97316', 'active' => true],
            ['name' => 'Muy Grande', 'code_prefix' => 'MG', 'code_start' => 1, 'code_end' => 5, 'price' => '7.00', 'color' => '#E5252A', 'active' => true],
        ];
    }
}
