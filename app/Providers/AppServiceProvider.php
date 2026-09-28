<?php

namespace App\Providers;

use App\Policies\ReportPolicy;
use App\Printing\LanEscPosPrintDriver;
use App\Printing\MockPrintDriver;
use App\Printing\PrintDriverInterface;
use App\Printing\WindowsUsbPrintDriver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PrintDriverInterface::class, function ($app): PrintDriverInterface {
            return match ($app['config']->get('printing.driver')) {
                'mock' => $app->make(MockPrintDriver::class),
                'lan_escpos' => $app->make(LanEscPosPrintDriver::class),
                'windows_usb' => $app->make(WindowsUsbPrintDriver::class),
                default => throw new InvalidArgumentException('Driver de impresión no soportado.'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewReports', [ReportPolicy::class, 'viewAny']);
    }
}
