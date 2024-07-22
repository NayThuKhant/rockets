<?php

namespace App\Enums;

enum RocketStatusEnum: string
{
    case LAUNCHED = 'launched';
    case DEPLOYED = 'deployed';
    case WAITING = 'waiting';
    case CANCELLED = 'cancelled';
}
