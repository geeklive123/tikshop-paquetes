<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PrintJobs\FailPrintJobAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FailPrintJobRequest;
use App\Models\PrinterAgent;
use App\Models\PrintJob;
use DomainException;
use Illuminate\Http\JsonResponse;

class FailedPrintJobController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        FailPrintJobRequest $request,
        string $printJob,
        FailPrintJobAction $failPrintJob,
    ): JsonResponse {
        /** @var PrinterAgent $agent */
        $agent = $request->attributes->get('printerAgent');
        $job = $this->findScopedJob($agent, $printJob);

        try {
            $job = $failPrintJob->execute($agent, $job, $request->validated('error_message'));

            return response()->json(['status' => $job->status->value]);
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
