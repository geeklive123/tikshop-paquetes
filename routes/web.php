<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PackagePickupTokenController;
use App\Http\Controllers\PickupController;
use App\Http\Controllers\PickupDeliveryController;
use App\Http\Controllers\ProfileController;
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
    Route::resource('packages', PackageController::class)->only(['index', 'create', 'store', 'show']);

    Route::patch('/users/{user}/status', UserStatusController::class)->name('users.status.update');
    Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
