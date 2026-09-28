<?php

namespace Tests\Feature;

use App\Actions\Packages\GeneratePickupQrCodeAction;
use App\Actions\Packages\ResolveTicketLogoAction;
use App\Enums\PrintJobStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackagePickupToken;
use App\Models\Printer;
use App\Models\PrintJob;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PrintJobManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_print_ticket_button_creates_snapshot_job_for_default_printer(): void
    {
        [$user, $package, $printer] = $this->printingContext(UserRole::Operator);
        $this->fakeTicketAssets();

        $response = $this->actingAs($user)->post(route('packages.print-ticket', $package));

        $response->assertRedirect(route('packages.show', $package));
        $response->assertSessionHas('status', 'Trabajo de impresión enviado');
        $job = PrintJob::query()->sole();
        $this->assertSame(PrintJobStatus::Pending, $job->status);
        $this->assertSame($printer->id, $job->printer_id);
        $this->assertSame($package->tracking_code, $job->payload['tracking_code']);
        $this->assertSame($package->branch->ticketAddress(), $job->payload['branch_address']);
        $this->assertSame('data:image/png;base64,cXItcG5n', $job->payload['qr_data_uri']);
        $this->assertSame('data:image/png;base64,bG9nbw==', $job->payload['logo_data_uri']);
        $this->assertDatabaseCount('print_job_events', 1);
    }

    public function test_recent_pending_job_prevents_accidental_duplicate(): void
    {
        [$user, $package] = $this->printingContext(UserRole::Operator);
        $this->fakeTicketAssets();

        $this->actingAs($user)->post(route('packages.print-ticket', $package))->assertRedirect();
        $this->actingAs($user)->post(route('packages.print-ticket', $package))->assertInvalid(['print_job']);

        $this->assertDatabaseCount('print_jobs', 1);
    }

    public function test_without_active_default_printer_no_job_is_created(): void
    {
        [$user, $package, $printer] = $this->printingContext(UserRole::Operator);
        $printer->update(['is_default' => false]);

        $this->actingAs($user)->post(route('packages.print-ticket', $package))->assertInvalid(['printer']);

        $this->assertDatabaseCount('print_jobs', 0);
    }

    public function test_print_jobs_panel_is_isolated_by_company_and_operator_sees_only_own_jobs(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $operator = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $otherOperator = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $printer = Printer::factory()->forBranch($branch)->create();
        PrintJob::factory()->forBranch($branch)->create([
            'printer_id' => $printer->id,
            'requested_by' => $operator->id,
            'payload' => ['tracking_code' => 'TIK-PROPIO'],
        ]);
        PrintJob::factory()->forBranch($branch)->create([
            'printer_id' => $printer->id,
            'requested_by' => $otherOperator->id,
            'payload' => ['tracking_code' => 'TIK-MISMA-EMPRESA-AJENO'],
        ]);
        PrintJob::factory()->create(['payload' => ['tracking_code' => 'TIK-OTRA-EMPRESA']]);

        $this->actingAs($operator)->get(route('print-jobs.index'))
            ->assertOk()
            ->assertSee('TIK-PROPIO')
            ->assertDontSee('TIK-MISMA-EMPRESA-AJENO')
            ->assertDontSee('TIK-OTRA-EMPRESA');
    }

    public function test_owner_can_retry_failed_job_and_cancel_pending_job(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $printer = Printer::factory()->forBranch($branch)->create();
        $failed = PrintJob::factory()->forBranch($branch)->failed()->create(['printer_id' => $printer->id, 'requested_by' => $owner->id]);
        $pending = PrintJob::factory()->forBranch($branch)->create(['printer_id' => $printer->id, 'requested_by' => $owner->id]);

        $this->actingAs($owner)->post(route('print-jobs.retry', $failed))->assertRedirect();
        $this->actingAs($owner)->post(route('print-jobs.cancel', $pending))->assertRedirect();

        $this->assertSame(PrintJobStatus::Pending, $failed->fresh()->status);
        $this->assertSame(1, $failed->fresh()->attempts);
        $this->assertSame(PrintJobStatus::Cancelled, $pending->fresh()->status);
    }

    public function test_operator_cannot_retry_jobs(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $operator = User::factory()->for($company)->withRole(UserRole::Operator)->create();
        $job = PrintJob::factory()->forBranch($branch)->failed()->create(['requested_by' => $operator->id]);

        $this->actingAs($operator)->post(route('print-jobs.retry', $job))->assertForbidden();
    }

    /** @return array{User, Package, Printer} */
    private function printingContext(UserRole $role): array
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $user = User::factory()->for($company)->withRole($role)->create();
        $package = Package::factory()->forBranch($branch)->create(['received_by' => $user->id]);
        PackagePickupToken::factory()->for($package)->create(['token_encrypted' => 'secure-token']);
        $printer = Printer::factory()->forBranch($branch)->default()->create();

        return [$user, $package, $printer];
    }

    private function fakeTicketAssets(): void
    {
        $this->mock(GeneratePickupQrCodeAction::class)->shouldReceive('executePng')->once()->andReturn('qr-png');
        $this->mock(ResolveTicketLogoAction::class)->shouldReceive('execute')->once()->andReturn('data:image/png;base64,bG9nbw==');
    }
}
