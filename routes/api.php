<?php

use App\Http\Controllers\Api\V1\CompletedPrintJobController;
use App\Http\Controllers\Api\V1\FailedPrintJobController;
use App\Http\Controllers\Api\V1\PendingPrintJobController;
use App\Http\Controllers\Api\V1\PrinterAgentHeartbeatController;
use App\Http\Controllers\Api\V1\ProcessingPrintJobController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/print-agent')
    ->middleware(['throttle:120,1', 'printer.agent'])
    ->group(function (): void {
        Route::post('/heartbeat', PrinterAgentHeartbeatController::class)->name('api.print-agent.heartbeat');
        Route::get('/jobs/next', PendingPrintJobController::class)->name('api.print-agent.jobs.next');
        Route::post('/jobs/{printJob}/processing', ProcessingPrintJobController::class)->name('api.print-agent.jobs.processing');
        Route::post('/jobs/{printJob}/completed', CompletedPrintJobController::class)->name('api.print-agent.jobs.completed');
        Route::post('/jobs/{printJob}/failed', FailedPrintJobController::class)->name('api.print-agent.jobs.failed');
    });
