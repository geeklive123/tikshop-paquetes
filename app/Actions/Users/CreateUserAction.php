<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUserAction
{
    /** @param array{name: string, email: string, password: string, role: string, active: bool} $data */
    public function execute(User $actor, array $data): User
    {
        return DB::transaction(fn (): User => User::query()->create([
            ...$data,
            'company_id' => $actor->company_id,
        ]));
    }
}
