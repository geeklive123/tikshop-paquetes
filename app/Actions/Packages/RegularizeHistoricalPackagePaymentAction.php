<?php

namespace App\Actions\Packages;

use App\Enums\PackageEventType;
use App\Enums\PackagePaymentStatus;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\PackageEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RegularizeHistoricalPackagePaymentAction
{
    private const string PaymentTimezone = 'America/La_Paz';

    public function execute(
        User $user,
        Package $package,
        string $paidAmount,
        CarbonInterface $paidDate,
        string $source,
    ): Package {
        return DB::transaction(function () use ($user, $package, $paidAmount, $paidDate, $source): Package {
            $lockedPackage = Package::query()
                ->whereKey($package->id)
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->first();

            if ($lockedPackage === null) {
                throw (new ModelNotFoundException)->setModel(Package::class);
            }

            Gate::forUser($user)->authorize('regularizeHistoricalPayment', $lockedPackage);

            $normalizedAmount = $this->normalizedAmount($paidAmount);
            $normalizedSource = trim($source);
            $historicalPaidAt = CarbonImmutable::instance($paidDate)->timezone(self::PaymentTimezone);

            $this->validateHistoricalData($lockedPackage, $historicalPaidAt, $normalizedSource);

            if ($lockedPackage->payment_status === PackagePaymentStatus::Paid) {
                if ($this->matchesExistingRegularization($lockedPackage, $normalizedAmount, $historicalPaidAt, $normalizedSource)) {
                    return $lockedPackage;
                }

                throw ValidationException::withMessages([
                    'payment' => 'El paquete ya tiene un pago registrado y no puede sobrescribirse.',
                ]);
            }

            $previousPaymentStatus = $lockedPackage->payment_status;

            $lockedPackage->update([
                'payment_status' => PackagePaymentStatus::Paid,
                'paid_amount' => $normalizedAmount,
                'paid_at' => $historicalPaidAt,
                'paid_by' => $user->id,
            ]);

            PackageEvent::query()->create([
                'package_id' => $lockedPackage->id,
                'user_id' => $user->id,
                'event' => PackageEventType::PackageHistoricalPaymentRegularized,
                'metadata' => [
                    'historical' => true,
                    'source' => $normalizedSource,
                    'confirmed_date' => $historicalPaidAt->toDateString(),
                    'exact_time_unknown' => true,
                    'paid_amount' => $normalizedAmount,
                    'previous_payment_status' => $previousPaymentStatus->value,
                    'regularized_at' => now(self::PaymentTimezone)->toISOString(),
                ],
            ]);

            return $lockedPackage;
        }, 5);
    }

    private function normalizedAmount(string $paidAmount): string
    {
        $amount = trim($paidAmount);

        if (preg_match('/\A\d{1,10}(?:\.\d{1,2})?\z/', $amount) !== 1 || (float) $amount <= 0) {
            throw ValidationException::withMessages([
                'paid_amount' => 'El monto histórico debe ser mayor que cero y tener hasta dos decimales.',
            ]);
        }

        return number_format((float) $amount, 2, '.', '');
    }

    private function validateHistoricalData(Package $package, CarbonImmutable $paidDate, string $source): void
    {
        if ($package->status === PackageStatus::Cancelled) {
            throw ValidationException::withMessages([
                'payment' => 'Un paquete cancelado no puede regularizarse como pagado.',
            ]);
        }

        if (! in_array($package->payment_status, [
            PackagePaymentStatus::Pending,
            PackagePaymentStatus::Paid,
            PackagePaymentStatus::Legacy,
        ], true)) {
            throw ValidationException::withMessages([
                'payment' => 'El paquete no tiene un estado financiero regularizable.',
            ]);
        }

        if ($source === '' || mb_strlen($source) > 100) {
            throw ValidationException::withMessages([
                'source' => 'La fuente de confirmación es obligatoria y no puede superar 100 caracteres.',
            ]);
        }

        $today = now(self::PaymentTimezone)->startOfDay();

        if ($paidDate->startOfDay()->isAfter($today)) {
            throw ValidationException::withMessages([
                'paid_date' => 'La fecha histórica no puede estar en el futuro.',
            ]);
        }

        if ($package->received_at?->timezone(self::PaymentTimezone)->startOfDay()->isAfter($paidDate->startOfDay())) {
            throw ValidationException::withMessages([
                'paid_date' => 'La fecha histórica no puede ser anterior a la recepción del paquete.',
            ]);
        }
    }

    private function matchesExistingRegularization(
        Package $package,
        string $paidAmount,
        CarbonImmutable $paidDate,
        string $source,
    ): bool {
        $event = $package->events()
            ->where('event', PackageEventType::PackageHistoricalPaymentRegularized)
            ->latest('id')
            ->first();

        if ($event === null || $package->paid_at === null) {
            return false;
        }

        return $package->paid_amount === $paidAmount
            && $package->paid_at->timezone(self::PaymentTimezone)->toDateString() === $paidDate->toDateString()
            && ($event->metadata['source'] ?? null) === $source
            && ($event->metadata['paid_amount'] ?? null) === $paidAmount
            && ($event->metadata['confirmed_date'] ?? null) === $paidDate->toDateString();
    }
}
