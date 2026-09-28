<?php

namespace App\Printing;

readonly class PrintResult
{
    public function __construct(
        public string $message,
        public ?string $evidencePath = null,
    ) {}
}
