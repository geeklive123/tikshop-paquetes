<?php

namespace App\Printing;

interface PrintDriverInterface
{
    /** @param array<string, mixed> $job */
    public function print(array $job): PrintResult;
}
