<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class WorkflowStatus extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}