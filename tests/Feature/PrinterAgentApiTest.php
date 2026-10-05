<?php

namespace Tests\Feature;

use App\Actions\PrinterAgents\CreatePrinterAgentAction;
use App\Enums\PrintJobStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Printer;
use App\Models\PrinterAgent;
use App\Models\PrintJob;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PrinterAgentApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_agent_token_is_stored_hashed_and_plain_token_is_returned_once(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $result = app(CreatePrinterAgentAction::class)->execute($owner, ['name' => 'PC caja', 'branch_id' => $branch->id]);

        $this->assertNotSame($result['plainToken'], $result['agent']->token_hash);
        $this->assertSame(hash('sha256', $result['plainToken']), $result['agent']->token_hash);
        $this->assertArrayNotHasKey('token_hash', $result['agent']->toArray());
    }

    public function test_owner_can_create_agent_but_operator_cannot_manage_agents(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $owner = User::factory()->for($company)->withRole(UserRole::Owner)->create();
        $operator = User::factory()->for($company)->withRole(UserRole::Operator)->create();

        $this->actingAs($owner)->post(route('printer-agents.store'), [
            'name' => 'PC principal',
            'branch_id' => $branch->id,
        ])->assertRedirect();
        $agent = PrinterAgent::query()->sole();

        $this->actingAs($operator)->get(route('printer-agents.edit', $agent))->assertForbidden();
        $this->actingAs($operator)->post(route('printer-agents.token', $agent))->assertForbidden();
    }

    public function test_agent_cannot_be_created_for_branch_from_another_company(): void
    {
        $owner = User::factory()->withRole(UserRole::Owner)->create();
        $otherBranch = Branch::factory()->create();

        $this->actingAs($owner)->post(route('printer-agents.store'), [
            'name' => 'PC ajena',
            'branch_id' => $otherBranch->id,
        ])->assertInvalid(['branch_id']);

        $this->assertDatabaseCount('printer_agents', 0);
    }

    public function test_heartbeat_authenticates_agent_and_updates_last_seen(): void
    {
        [$agent, $token] = $this->agentContext();

        $this->withToken($token)->postJson(route('api.print-agent.heartbeat'))->assertOk()->assertJsonPath('status', 'ok');

        $this->assertNotNull($agent->fresh()->last_seen_at);
        $this->withToken('invalid')->postJson(route('api.print-agent.heartbeat'))->assertUnauthorized();
    }

    public function test_agent_only_receives_pending_jobs_from_its_company_and_branch(): void
    {
        [, $token, $branch] = $this->agentContext();
        $ownJob = PrintJob::factory()->forBranch($branch)->create();
        $otherBranch = Branch::factory()->for($branch->company)->create();
        PrintJob::factory()->forBranch($otherBranch)->create();
        PrintJob::factory()->create();

        $this->withToken($token)->getJson(route('api.print-agent.jobs.next'))
            ->assertOk()->assertJsonPath('data.ulid', $ownJob->ulid)->assertJsonMissingPath('data.company_id');
    }

    public function test_pending_job_response_preserves_ticket_copies_snapshot(): void
    {
        [, $token, $branch] = $this->agentContext();
        $job = PrintJob::factory()->forBranch($branch)->create([
            'payload' => ['tracking_code' => 'TIK-COPIES', 'copies' => 2],
        ]);

        $this->withToken($token)->getJson(route('api.print-agent.jobs.next'))
            ->assertOk()
            ->assertJsonPath('data.ulid', $job->ulid)
            ->assertJsonPath('data.payload.copies', 2);
    }

    public function test_agent_cannot_claim_job_from_another_branch_or_company(): void
    {
        [, $token, $branch] = $this->agentContext();
        $otherBranch = Branch::factory()->for($branch->company)->create();
        $branchJob = PrintJob::factory()->forBranch($otherBranch)->create();
        $companyJob = PrintJob::factory()->create();

        $this->withToken($token)->postJson(route('api.print-agent.jobs.processing', $branchJob))->assertNotFound();
        $this->withToken($token)->postJson(route('api.print-agent.jobs.processing', $companyJob))->assertNotFound();
    }

    public function test_job_transitions_from_pending_to_processing_to_completed(): void
    {
        [$agent, $token, $branch] = $this->agentContext();
        $job = PrintJob::factory()->forBranch($branch)->create();

        $this->withToken($token)->postJson(route('api.print-agent.jobs.processing', $job))
            ->assertOk()->assertJsonPath('data.status', PrintJobStatus::Processing->value)->assertJsonPath('data.attempts', 1);
        $this->withToken($token)->postJson(route('api.print-agent.jobs.completed', $job), [
            'result_message' => 'Evidencia: prints/ticket.html',
        ])->assertOk()->assertJsonPath('status', PrintJobStatus::Completed->value);

        $job->refresh();
        $this->assertSame(PrintJobStatus::Completed, $job->status);
        $this->assertSame($agent->id, $job->printer_agent_id);
        $this->assertNotNull($job->completed_at);
        $this->assertDatabaseCount('print_job_events', 2);
    }

    public function test_job_can_fail_then_retry_and_next_claim_increments_attempts(): void
    {
        [, $token, $branch] = $this->agentContext();
        $owner = User::factory()->for($branch->company)->withRole(UserRole::Owner)->create();
        $job = PrintJob::factory()->forBranch($branch)->create(['requested_by' => $owner->id]);

        $this->withToken($token)->postJson(route('api.print-agent.jobs.processing', $job))->assertOk();
        $this->withToken($token)->postJson(route('api.print-agent.jobs.failed', $job), ['error_message' => 'Papel agotado simulado'])
            ->assertOk()->assertJsonPath('status', PrintJobStatus::Failed->value);
        $this->actingAs($owner)->post(route('print-jobs.retry', $job))->assertRedirect();
        $this->withToken($token)->postJson(route('api.print-agent.jobs.processing', $job))
            ->assertOk()->assertJsonPath('data.attempts', 2);

        $this->assertSame(2, $job->fresh()->attempts);
    }

    /** @return array{PrinterAgent, string, Branch} */
    private function agentContext(): array
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $token = 'agent-token-'.fake()->uuid();
        $agent = PrinterAgent::factory()->forBranch($branch)->create(['token_hash' => hash('sha256', $token)]);
        Printer::factory()->forBranch($branch)->create();

        return [$agent, $token, $branch];
    }
}
