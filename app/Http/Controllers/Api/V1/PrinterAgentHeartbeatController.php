<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PrinterAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrinterAgentHeartbeatController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var PrinterAgent $agent */
        $agent = $request->attributes->get('printerAgent');
        $agent->update(['last_seen_at' => now()]);

        return response()->json([
            'status' => 'ok',
            'last_seen_at' => $agent->last_seen_at?->toIso8601String(),
        ]);
    }
}
