<?php

use App\Http\Controllers\CancellationReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PackageCancellationController;
use App\Http\Controllers\PackageCategoryController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PackagePickupQrImageController;
use App\Http\Controllers\PackagePickupTokenController;
use App\Http\Controllers\PackagePrintJobController;
use App\Http\Controllers\PackageReportController;
use App\Http\Controllers\PackageTicketController;
use App\Http\Controllers\PaidCommissionReportController;
use App\Http\Controllers\PickupController;
use App\Http\Controllers\PickupDeliveryController;
use App\Http\Controllers\PrinterAgentController;
use App\Http\Controllers\PrinterAgentStatusController;
use App\Http\Controllers\PrinterAgentTokenController;
use App\Http\Controllers\PrinterConnectionTestController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\PrinterStatusController;
use App\Http\Controllers\PrintJobCancellationController;
use App\Http\Controllers\PrintJobController;
use App\Http\Controllers\PrintJobRetryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SellerCommissionController;
use App\Http\Controllers\SellerCommissionSettlementController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SellerReportController;
use App\Http\Controllers\SellerStatusController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserStatusController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'active', 'sensitive'])
    ->name('dashboard');

Route::middleware(['auth', 'active', 'sensitive'])->group(function () {
    Route::get('/pickup', [PickupController::class, 'index'])->name('pickup.scanner');
    Route::post('/pickup/resolve', [PickupController::class, 'resolve'])->name('pickup.resolve');
    Route::post('/pickup/manual', [PickupController::class, 'manual'])->name('pickup.manual');
    Route::post('/pickup/deliver', [PickupDeliveryController::class, 'fromQr'])->name('pickup.deliver');
    Route::get('/pickup/{token}', [PickupController::class, 'show'])
        ->whereAlphaNumeric('token')
        ->name('pickup.show');

    Route::get('/packages/{package}/success', [PackageController::class, 'success'])
        ->name('packages.success');
    Route::post('/packages/{package}/pickup-token', PackagePickupTokenController::class)
        ->name('packages.regenerate-qr');
    Route::get('/packages/{package}/pickup-qr.png', PackagePickupQrImageController::class)
        ->name('packages.pickup-qr.download');
    Route::get('/packages/{package}/ticket', PackageTicketController::class)
        ->name('packages.ticket');
    Route::get('/packages/{package}/ticket/download', PackageTicketController::class)
        ->name('packages.ticket.download');
    Route::post('/packages/{package}/print-ticket', PackagePrintJobController::class)
        ->name('packages.print-ticket');
    Route::post('/packages/{package}/deliver', [PickupDeliveryController::class, 'manually'])
        ->name('packages.deliver');
    Route::post('/packages/{package}/cancel', PackageCancellationController::class)
        ->name('packages.cancel');
    Route::resource('packages', PackageController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    Route::resource('package-categories', PackageCategoryController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);

    Route::patch('/printers/{printer}/status', PrinterStatusController::class)->name('printers.status.update');
    Route::post('/printers/{printer}/test-connection', PrinterConnectionTestController::class)->name('printers.test-connection');
    Route::resource('printers', PrinterController::class)->only(['index', 'create', 'store', 'edit', 'update']);

    Route::get('/print-jobs', [PrintJobController::class, 'index'])->name('print-jobs.index');
    Route::post('/print-jobs/{printJob}/retry', PrintJobRetryController::class)->name('print-jobs.retry');
    Route::post('/print-jobs/{printJob}/cancel', PrintJobCancellationController::class)->name('print-jobs.cancel');

    Route::get('/printer-agents/create', [PrinterAgentController::class, 'create'])->name('printer-agents.create');
    Route::post('/printer-agents', [PrinterAgentController::class, 'store'])->name('printer-agents.store');
    Route::get('/printer-agents/{printerAgent}/edit', [PrinterAgentController::class, 'edit'])->name('printer-agents.edit');
    Route::put('/printer-agents/{printerAgent}', [PrinterAgentController::class, 'update'])->name('printer-agents.update');
    Route::post('/printer-agents/{printerAgent}/token', PrinterAgentTokenController::class)->name('printer-agents.token');
    Route::patch('/printer-agents/{printerAgent}/status', PrinterAgentStatusController::class)->name('printer-agents.status');

    Route::patch('/sellers/{seller}/status', SellerStatusController::class)->name('sellers.status.update');
    Route::get('/sellers/{seller}/commissions', [SellerCommissionController::class, 'index'])->name('sellers.commissions.index');
    Route::get('/sellers/{seller}/commissions/calculate', [SellerCommissionController::class, 'calculate'])->name('sellers.commissions.calculate');
    Route::post('/sellers/{seller}/commission-settlements', [SellerCommissionSettlementController::class, 'store'])->name('sellers.commission-settlements.store');
    Route::get('/sellers/{seller}/commission-settlements/{sellerCommissionSettlement}', [SellerCommissionSettlementController::class, 'show'])
        ->scopeBindings()
        ->name('sellers.commission-settlements.show');
    Route::resource('sellers', SellerController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    Route::get('/reports', ReportController::class)->name('reports.index');
    Route::get('/reports/packages', [PackageReportController::class, 'index'])->name('reports.packages.index');
    Route::get('/reports/sellers', [SellerReportController::class, 'index'])->name('reports.sellers.index');
    Route::get('/reports/sellers/{seller}', [SellerReportController::class, 'show'])->name('reports.sellers.show');
    Route::get('/reports/cancellations', [CancellationReportController::class, 'index'])->name('reports.cancellations.index');
    Route::get('/reports/commissions', PaidCommissionReportController::class)->name('reports.commissions.index');

    Route::patch('/users/{user}/status', UserStatusController::class)->name('users.status.update');
    Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
