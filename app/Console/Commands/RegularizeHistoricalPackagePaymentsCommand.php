<?php

namespace App\Console\Commands;

use App\Actions\Packages\RegularizeHistoricalPackagePaymentAction;
use App\Enums\PackagePaymentStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Package;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

#[Signature('packages:regularize-historical-payments
    {--company= : ID de la empresa propietaria de los paquetes}
    {--actor-email= : Correo del owner/admin que ejecuta la regularización}
    {--paid-date= : Fecha histórica confirmada en formato Y-m-d}
    {--source=client_confirmation : Fuente o motivo de la confirmación}
    {--payment=* : Pago explícito TRACKING_CODE:MONTO; puede repetirse}
    {--execute : Aplicar los cambios; sin esta opción solo valida y simula}
    {--force-production : Autorizar explícitamente la ejecución en producción}')]
#[Description('Regulariza pagos históricos explícitos de almacenaje con auditoría y alcance por empresa')]
class RegularizeHistoricalPackagePaymentsCommand extends Command
{
    private const string PaymentTimezone = 'America/La_Paz';

    public function handle(RegularizeHistoricalPackagePaymentAction $regularizePayment): int
    {
        $isProduction = app()->isProduction();

        if (! $isProduction && ! app()->environment(['local', 'testing'])) {
            $this->error('Este comando solo puede ejecutarse en local, testing o producción controlada.');

            return self::FAILURE;
        }

        if ($isProduction && ! $this->option('force-production')) {
            $this->error('En producción debes proporcionar explícitamente --force-production.');

            return self::FAILURE;
        }

        $context = $this->resolveContext();

        if ($context === null) {
            return self::FAILURE;
        }

        [$company, $actor, $paidDate, $source, $payments] = $context;
        $packages = Package::query()
            ->where('company_id', $company->id)
            ->whereIn('tracking_code', array_keys($payments))
            ->get()
            ->keyBy('tracking_code');
        $missingTrackingCodes = array_values(array_diff(array_keys($payments), $packages->keys()->all()));

        if ($missingTrackingCodes !== []) {
            $this->error('No se encontraron en la empresa: '.implode(', ', $missingTrackingCodes));

            return self::FAILURE;
        }

        $this->table(
            ['Tracking', 'Estado de pago actual', 'Monto histórico'],
            collect($payments)->map(fn (string $amount, string $trackingCode): array => [
                $trackingCode,
                $packages->get($trackingCode)->payment_status?->value ?? 'sin estado',
                'Bs '.$amount,
            ])->values()->all(),
        );
        $this->line(sprintf('Total explícito: %d paquetes · Bs %s', count($payments), $this->sumAmounts($payments)));

        try {
            $operation = function () use ($regularizePayment, $actor, $packages, $payments, $paidDate, $source): array {
                $regularized = 0;
                $idempotent = 0;

                foreach ($payments as $trackingCode => $amount) {
                    /** @var Package $package */
                    $package = $packages->get($trackingCode);
                    $wasPaid = $package->payment_status === PackagePaymentStatus::Paid;
                    $regularizePayment->execute($actor, $package, $amount, $paidDate, $source);
                    $wasPaid ? $idempotent++ : $regularized++;
                }

                return [$regularized, $idempotent];
            };

            if (! $this->option('execute')) {
                DB::beginTransaction();

                try {
                    [$regularized, $idempotent] = $operation();
                } finally {
                    DB::rollBack();
                }

                $this->warn(sprintf(
                    'Simulación válida: %d por regularizar y %d ya idempotentes. No se guardó ningún cambio.',
                    $regularized,
                    $idempotent,
                ));

                return self::SUCCESS;
            }

            if ($isProduction && ! $this->confirm(
                sprintf(
                    'PRODUCCIÓN: ¿Confirmas regularizar %d paquetes por Bs %s con fecha %s?',
                    count($payments),
                    $this->sumAmounts($payments),
                    $paidDate->toDateString(),
                ),
                false,
            )) {
                $this->warn('Regularización cancelada. No se guardó ningún cambio.');

                return self::FAILURE;
            }

            [$regularized, $idempotent] = DB::transaction($operation, 5);
        } catch (Throwable $exception) {
            $message = $exception instanceof ValidationException
                ? $exception->validator->errors()->first()
                : $exception->getMessage();
            $this->error('Regularización rechazada: '.$message);

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Regularización completada: %d actualizados, %d ya idempotentes, total confirmado Bs %s.',
            $regularized,
            $idempotent,
            $this->sumAmounts($payments),
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{Company, User, CarbonImmutable, string, array<string, string>}|null
     */
    private function resolveContext(): ?array
    {
        $companyId = filter_var($this->option('company'), FILTER_VALIDATE_INT);
        $actorEmail = trim((string) $this->option('actor-email'));
        $paidDateInput = trim((string) $this->option('paid-date'));
        $source = trim((string) $this->option('source'));
        $paymentOptions = $this->option('payment');

        if ($companyId === false || $companyId < 1 || $actorEmail === '' || $paidDateInput === '' || ! is_array($paymentOptions) || $paymentOptions === []) {
            $this->error('Debes indicar --company, --actor-email, --paid-date y al menos un --payment.');

            return null;
        }

        if (! CarbonImmutable::hasFormat($paidDateInput, 'Y-m-d')) {
            $this->error('--paid-date debe usar el formato Y-m-d.');

            return null;
        }

        $company = Company::query()->find($companyId);
        $actor = User::query()
            ->where('company_id', $companyId)
            ->where('email', $actorEmail)
            ->where('active', true)
            ->first();

        if ($company === null || $actor === null) {
            $this->error('La empresa o el usuario activo indicado no existe dentro de ese alcance.');

            return null;
        }

        if (! in_array($actor->role, [UserRole::Owner, UserRole::Admin], true)) {
            $this->error('El actor debe tener rol owner o admin.');

            return null;
        }

        $payments = $this->parsePayments($paymentOptions);

        if ($payments === null || $source === '') {
            return null;
        }

        return [
            $company,
            $actor,
            CarbonImmutable::createFromFormat('Y-m-d', $paidDateInput, self::PaymentTimezone)->startOfDay(),
            $source,
            $payments,
        ];
    }

    /**
     * @param  array<int, mixed>  $paymentOptions
     * @return array<string, string>|null
     */
    private function parsePayments(array $paymentOptions): ?array
    {
        $payments = [];

        foreach ($paymentOptions as $paymentOption) {
            if (! is_string($paymentOption) || preg_match('/\A([^:]+):(\d{1,10}(?:\.\d{1,2})?)\z/', trim($paymentOption), $matches) !== 1) {
                $this->error('Cada --payment debe usar TRACKING_CODE:MONTO con hasta dos decimales.');

                return null;
            }

            $trackingCode = trim($matches[1]);
            $amount = number_format((float) $matches[2], 2, '.', '');

            if ((float) $amount <= 0 || isset($payments[$trackingCode])) {
                $this->error('Los montos deben ser mayores a cero y cada tracking debe aparecer una sola vez.');

                return null;
            }

            $payments[$trackingCode] = $amount;
        }

        return $payments;
    }

    /** @param array<string, string> $payments */
    private function sumAmounts(array $payments): string
    {
        $totalCents = array_sum(array_map(
            fn (string $amount): int => (int) round((float) $amount * 100),
            $payments,
        ));

        return number_format($totalCents / 100, 2, '.', '');
    }
}
