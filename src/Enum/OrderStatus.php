<?php

namespace App\Enum;

enum OrderStatus: string
{
    case PAID = 'paid';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
