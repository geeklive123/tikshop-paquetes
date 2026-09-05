<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Operator = 'operator';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Administrador',
            self::Operator => 'Operador',
        };
    }

    public function canManageUsers(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    public function canManage(self $role): bool
    {
        return match ($this) {
            self::Owner => true,
            self::Admin => $role !== self::Owner,
            self::Operator => false,
        };
    }

    /** @return array<int, self> */
    public function manageableRoles(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $role): bool => $this->canManage($role),
        ));
    }
}
