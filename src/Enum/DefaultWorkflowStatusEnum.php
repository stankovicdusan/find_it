<?php

namespace App\Enum;

enum DefaultWorkflowStatusEnum: string
{
    case BACKLOG     = 'Backlog';
    case IN_PROGRESS = 'In progress';
    case DONE        = 'Done';

    public function color(): string
    {
        return match ($this) {
            self::BACKLOG => '#6c757d',
            self::IN_PROGRESS => '#0d6efd',
            self::DONE => '#198754',
        };
    }
}
