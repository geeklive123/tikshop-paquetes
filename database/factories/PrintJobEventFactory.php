<?php

namespace Database\Factories;

use App\Enums\PrintJobEventType;
use App\Models\PrintJob;
use App\Models\PrintJobEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrintJobEvent>
 */
class PrintJobEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'print_job_id' => PrintJob::factory(),
            'printer_agent_id' => null,
            'user_id' => null,
            'type' => PrintJobEventType::Requested,
            'message' => null,
            'metadata' => null,
        ];
    }
}
