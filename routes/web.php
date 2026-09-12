<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PackageCategoryController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PackagePickupTokenController;
use App\Http\Controllers\PackageTicketController;
use App\Http\Controllers\PickupController;
use App\Http\Controllers\PickupDeliveryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SellerController;
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
    Route::post('/pickup/deliver', PickupDeliveryController::class)->name('pickup.deliver');
    Route::get('/pickup/{token}', [PickupController::class, 'show'])
        ->whereAlphaNumeric('token')
        ->name('pickup.show');

    Route::get('/packages/{package}/success', [PackageController::class, 'success'])
        ->name('packages.success');
    Route::post('/packages/{package}/pickup-token', PackagePickupTokenController::class)
        ->name('packages.regenerate-qr');
    Route::get('/packages/{package}/ticket', PackageTicketController::class)
        ->name('packages.ticket');
    Route::get('/packages/{package}/ticket/download', PackageTicketController::class)
        ->name('packages.ticket.download');
    Route::resource('packages', PackageController::class)->only(['index', 'create', 'store', 'show']);

    Route::resource('package-categories', PackageCategoryController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);

    Route::patch('/sellers/{seller}/status', SellerStatusController::class)->name('sellers.status.update');
    Route::resource('sellers', SellerController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    Route::patch('/users/{user}/status', UserStatusController::class)->name('users.status.update');
    Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
