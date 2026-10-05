<?php

namespace App\Actions\PrintJobs;

use App\Actions\Packages\CalculatePackageStorageAmountAction;
use App\Actions\Packages\GeneratePickupQrCodeAction;
use App\Actions\Packages\ResolveTicketLogoAction;
use App\Enums\PackageStatus;
use App\Enums\PrintJobEventType;
use App\Enums\PrintJobStatus;
use App\Enums\PrintJobType;
use App\Models\Package;
use App\Models\Printer;
use App\Models\PrintJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePackageTicketPrintJobAction
{
    public function __construct(
        private GeneratePickupQrCodeAction $generatePickupQrCode,
        private ResolveTicketLogoAction $resolveTicketLogo,
        private CalculatePackageStorageAmountAction $calculateStorageAmount,
    ) {}

    /** @throws ValidationException */
    public function execute(User $user, Package $package): PrintJob
    {
        return DB::transaction(function () use ($user, $package): PrintJob {
            $lockedPackage = Package::query()
                ->whereKey($package->getKey())
                ->where('company_id', $user->company_id)
                ->with(['branch:id,name,address', 'category:id,name'])
                ->lockForUpdate()
                ->firstOrFail();
            $printer = Printer::query()
                ->where('company_id', $user->company_id)
                ->where('branch_id', $lockedPackage->branch_id)
                ->where('active', true)
                ->where('is_default', true)
                ->lockForUpdate()
                ->first();

            if ($printer === null) {
                throw ValidationException::withMessages([
                    'printer' => 'No existe una impresora predeterminada activa para esta sucursal.',
                ]);
            }

            $duplicateExists = PrintJob::query()
                ->where('company_id', $user->company_id)
                ->where('package_id', $lockedPackage->id)
                ->where('type', PrintJobType::PackageTicket)
                ->whereIn('status', [PrintJobStatus::Pending, PrintJobStatus::Processing])
                ->where('created_at', '>=', now()->subSeconds((int) config('printing.duplicate_window_seconds', 120)))
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'print_job' => 'Ya existe un trabajo de impresión reciente para este paquete.',
                ]);
            }

            $tokenQuery = $lockedPackage->pickupTokens()->latest('id');

            if ($lockedPackage->status !== PackageStatus::Delivered) {
                $tokenQuery
                    ->whereNull('used_at')
                    ->whereNull('revoked_at')
                    ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
            }

            $activeToken = $tokenQuery->first();
            $rawToken = $activeToken?->token_encrypted;

            if (! is_string($rawToken) || $rawToken === '') {
                throw ValidationException::withMessages([
                    'print_job' => 'El paquete no tiene un QR activo para incluir en el ticket.',
                ]);
            }

            $storageAmount = $this->calculateStorageAmount->execute($lockedPackage);
            $job = PrintJob::query()->create([
                'company_id' => $user->company_id,
                'branch_id' => $lockedPackage->branch_id,
                'printer_id' => $printer->id,
                'package_id' => $lockedPackage->id,
                'type' => PrintJobType::PackageTicket,
                'status' => PrintJobStatus::Pending,
                'attempts' => 0,
                'payload' => [
                    'tracking_code' => $lockedPackage->tracking_code,
                    'branch_name' => $lockedPackage->branch->name,
                    'branch_address' => $lockedPackage->branch->ticketAddress(),
                    'storage_code' => $lockedPackage->storage_code,
                    'category_name' => $lockedPackage->category?->name,
                    'sender_name' => $lockedPackage->sender_name,
                    'recipient_name' => $lockedPackage->recipient_name,
                    'recipient_phone' => $lockedPackage->recipient_phone,
                    'status' => $lockedPackage->status->value,
                    'status_label' => $lockedPackage->status->label(),
                    'description' => $lockedPackage->description,
                    'storage_price' => $lockedPackage->storage_price,
                    'storage_base_amount' => $storageAmount['baseAmount'],
                    'storage_days' => $storageAmount['daysStored'],
                    'storage_surcharge_amount' => $storageAmount['surchargeAmount'],
                    'storage_total_amount' => $storageAmount['totalAmount'],
                    'copies' => $printer->copies,
                    'received_at' => $lockedPackage->received_at?->toIso8601String(),
                    'qr_data_uri' => 'data:image/png;base64,'.base64_encode(
                        $this->generatePickupQrCode->executePng(route('pickup.show', ['token' => $rawToken])),
                    ),
                    'logo_data_uri' => $this->resolveTicketLogo->execute(),
                ],
                'requested_by' => $user->id,
            ]);

            $job->events()->create([
                'user_id' => $user->id,
                'type' => PrintJobEventType::Requested,
            ]);

            return $job;
        });
    }
}
