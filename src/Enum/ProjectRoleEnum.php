<?php

namespace App\Enum;

enum ProjectRoleEnum: string
{
    case ADMIN  = 'ADMIN';
    case MEMBER = 'MEMBER';

    public static function fromString(?string $value, self $default = self::MEMBER): self
    {
        if (null === $value) {
            return $default;
        }

        $norm = strtoupper(trim($value));

        return self::tryFrom($norm) ?? $default;
    }
}
