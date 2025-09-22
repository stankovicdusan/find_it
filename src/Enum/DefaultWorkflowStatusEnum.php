<?php

namespace App\Enum;

enum DefaultWorkflowStatusEnum: string
{
    case BACKLOG     = 'Backlog';
    case IN_PROGRESS = 'In progress';
    case DONE        = 'Done';
}
