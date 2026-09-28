<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PrintJobStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PrintJobResource;
use App\Models\PrinterAgent;
use App\Models\PrintJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

class PendingPrintJobController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResource|Response
    {
        /** @var PrinterAgent $agent */
        $agent = $request->attributes->get('printerAgent');

        $job = PrintJob::query()
            ->where('company_id', $agent->company_id)
            ->where('branch_id', $agent->branch_id)
            ->where('status', PrintJobStatus::Pending)
            ->whereHas('printer', fn ($query) => $query
                ->where('company_id', $agent->company_id)
                ->where('branch_id', $agent->branch_id)
                ->where('active', true))
            ->with('printer')
            ->oldest()
            ->first();

        if ($job === null) {
            return response()->noContent();
        }

        return new PrintJobResource($job);
    }
}
