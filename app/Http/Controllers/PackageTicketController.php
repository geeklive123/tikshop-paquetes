<?php

namespace App\Http\Controllers;

use App\Actions\Packages\CalculatePackageStorageAmountAction;
use App\Actions\Packages\GeneratePickupQrCodeAction;
use App\Actions\Packages\ResolveTicketLogoAction;
use App\Enums\PackageStatus;
use App\Models\Package;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PackageTicketController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        Package $package,
        GeneratePickupQrCodeAction $generatePickupQrCode,
        ResolveTicketLogoAction $resolveTicketLogo,
        CalculatePackageStorageAmountAction $calculateStorageAmount,
    ): Response {
        Gate::authorize('view', $package);

        $package->load(['company:id,name', 'branch:id,name,address', 'category:id,name']);
        $tokenQuery = $package->pickupTokens()->latest('id');

        if ($package->status !== PackageStatus::Delivered) {
            $tokenQuery
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
        }

        $activeToken = $tokenQuery->first();

        $rawToken = $activeToken?->token_encrypted;

        if (! is_string($rawToken) || $rawToken === '') {
            abort(404);
        }

        $temporaryDirectory = storage_path('app/tmp');
        File::ensureDirectoryExists($temporaryDirectory);

        $temporaryQrPath = $temporaryDirectory.DIRECTORY_SEPARATOR.'qr-'.Str::random(40).'.png';

        try {
            $qrPng = $generatePickupQrCode->executePng(route('pickup.show', ['token' => $rawToken]));

            if (File::put($temporaryQrPath, $qrPng) === false) {
                throw new \RuntimeException('No se pudo crear la imagen QR temporal.');
            }

            $qrImagePath = 'file://'.str_replace('\\', '/', $temporaryQrPath);
            $pdf = Pdf::loadView('packages.ticket', [
                'package' => $package,
                'qrImagePath' => $qrImagePath,
                'logoDataUri' => $resolveTicketLogo->execute(),
                'storageAmount' => $calculateStorageAmount->execute($package),
            ])->setPaper([0, 0, 226.77, 510.24]);
            $filename = "ticket-{$package->tracking_code}.pdf";

            $pdf->render();

            return $request->routeIs('packages.ticket.download')
                ? $pdf->download($filename)
                : $pdf->stream($filename);
        } finally {
            File::delete($temporaryQrPath);
        }
    }
}
