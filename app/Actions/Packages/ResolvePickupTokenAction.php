<?php

namespace App\Actions\Packages;

use App\Enums\PackageEventType;
use App\Enums\PackageStatus;
use App\Models\PackageEvent;
use App\Models\PackagePickupToken;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ResolvePickupTokenAction
{
    public function execute(User $user, string $input): PackagePickupToken
    {
        $pickupToken = PackagePickupToken::query()
            ->with('package')
            ->where('token_hash', self::hashFromInput($input))
            ->first();

        if ($pickupToken === null) {
            throw ValidationException::withMessages(['token' => 'El código QR no es válido.']);
        }

        if ($pickupToken->package->company_id !== $user->company_id) {
            throw (new ModelNotFoundException)->setModel(PackagePickupToken::class);
        }

        $this->ensureUsable($pickupToken);

        PackageEvent::query()->create([
            'package_id' => $pickupToken->package_id,
            'user_id' => $user->id,
            'event' => PackageEventType::QrScanned,
            'metadata' => ['pickup_token_id' => $pickupToken->id],
        ]);

        return $pickupToken;
    }

    public static function hashFromInput(string $input): string
    {
        return hash('sha256', self::rawTokenFromInput($input));
    }

    public static function rawTokenFromInput(string $input): string
    {
        $input = trim($input);

        if (preg_match('/\A[A-Za-z0-9]{64}\z/', $input) === 1) {
            return $input;
        }

        $path = parse_url($input, PHP_URL_PATH);
        $rawToken = is_string($path) ? basename(rtrim($path, '/')) : '';

        if (preg_match('/\A[A-Za-z0-9]{64}\z/', $rawToken) !== 1) {
            throw ValidationException::withMessages(['token' => 'El código QR no es válido.']);
        }

        return $rawToken;
    }

    private function ensureUsable(PackagePickupToken $pickupToken): void
    {
        if ($pickupToken->used_at !== null) {
            throw ValidationException::withMessages(['token' => 'Este código ya fue utilizado.']);
        }

        if ($pickupToken->revoked_at !== null) {
            throw ValidationException::withMessages(['token' => 'Este código fue reemplazado y ya no es válido.']);
        }

        if ($pickupToken->expires_at?->isPast()) {
            throw ValidationException::withMessages(['token' => 'Este código QR ha expirado.']);
        }

        if ($pickupToken->package->status === PackageStatus::Cancelled) {
            throw ValidationException::withMessages(['token' => 'Este paquete fue cancelado y no puede entregarse.']);
        }

        if ($pickupToken->package->status === PackageStatus::Delivered) {
            throw ValidationException::withMessages(['token' => 'Este paquete ya fue entregado.']);
        }
    }
}
