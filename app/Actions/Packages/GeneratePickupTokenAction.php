<?php

namespace App\Actions\Packages;

use App\Enums\PackageEventType;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\PackageEvent;
use App\Models\PackagePickupToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GeneratePickupTokenAction
{
    /**
     * @return array{package: Package, pickupToken: PackagePickupToken, rawToken: string}
     */
    public function execute(User $user, Package $package): array
    {
        return DB::transaction(function () use ($user, $package): array {
            $package = Package::query()
                ->whereKey($package->getKey())
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($package->status, [PackageStatus::Delivered, PackageStatus::Cancelled], true)) {
                throw ValidationException::withMessages([
                    'package' => 'No se puede generar un QR para un paquete entregado o cancelado.',
                ]);
            }

            $isRegeneration = $package->pickupTokens()->exists();
            $generatedAt = now();

            $package->pickupTokens()
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => $generatedAt]);

            $rawToken = Str::random(64);
            $pickupToken = $package->pickupTokens()->create([
                'token_hash' => hash('sha256', $rawToken),
                'token_encrypted' => $rawToken,
            ]);

            if ($package->status === PackageStatus::Received) {
                $package->update([
                    'status' => PackageStatus::ReadyForPickup,
                    'ready_at' => $generatedAt,
                ]);
            }

            PackageEvent::query()->create([
                'package_id' => $package->id,
                'user_id' => $user->id,
                'event' => $isRegeneration ? PackageEventType::QrRegenerated : PackageEventType::QrGenerated,
                'metadata' => ['pickup_token_id' => $pickupToken->id],
            ]);

            return [
                'package' => $package->fresh(),
                'pickupToken' => $pickupToken,
                'rawToken' => $rawToken,
            ];
        }, 5);
    }
}
