<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
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
}
