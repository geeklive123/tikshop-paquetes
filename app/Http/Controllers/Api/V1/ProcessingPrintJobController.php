<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PrintJobs\ClaimPrintJobAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\PrintJobResource;
use App\Models\PrinterAgent;
use App\Models\PrintJob;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessingPrintJobController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        string $printJob,
        ClaimPrintJobAction $claimPrintJob,
    ): JsonResource|JsonResponse {
        /** @var PrinterAgent $agent */
        $agent = $request->attributes->get('printerAgent');
        $job = $this->findScopedJob($agent, $printJob);

        try {
            return new PrintJobResource($claimPrintJob->execute($agent, $job));
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }
    }

    private function findScopedJob(PrinterAgent $agent, string $ulid): PrintJob
    {
        return PrintJob::query()
            ->where('ulid', $ulid)
            ->where('company_id', $agent->company_id)
            ->where('branch_id', $agent->branch_id)
            ->firstOrFail();
    }
}
