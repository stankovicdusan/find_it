<?php

namespace App\Enum;

enum SprintStateEnum: string
{
    case PLANNED   = 'PLANNED';
    case ACTIVE    = 'ACTIVE';
    case COMPLETED = 'COMPLETED';
}
