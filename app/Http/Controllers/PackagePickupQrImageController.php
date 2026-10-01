<?php

namespace App\Http\Controllers;

use App\Actions\Packages\GenerateShareablePickupQrImageAction;
use App\Models\Package;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PackagePickupQrImageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Package $package,
        GenerateShareablePickupQrImageAction $generateShareablePickupQrImage,
    ): Response {
        Gate::authorize('sharePickupQr', $package);

        $png = $generateShareablePickupQrImage->execute($package);
        $filename = "qr-{$package->tracking_code}.png";

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($png),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
