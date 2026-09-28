<?php

namespace App\Enums;

enum PrintJobEventType: string
{
    case Requested = 'requested';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Retried = 'retried';
    case Cancelled = 'cancelled';
}
