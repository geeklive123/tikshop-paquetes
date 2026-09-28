<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrintJobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'attempts' => $this->attempts,
            'payload' => $this->payload,
            'printer' => [
                'ulid' => $this->printer->ulid,
                'name' => $this->printer->name,
                'connection_type' => $this->printer->connection_type->value,
                'ip_address' => $this->printer->ip_address,
                'port' => $this->printer->port,
                'paper_width' => $this->printer->paper_width,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
