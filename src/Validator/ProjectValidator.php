<?php

namespace App\Validator;

use App\Model\Dto\CreateProjectDto;
use App\Service\ProjectService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ProjectValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {
    }

    /**
     * @param CreateProjectDto $dto
     */
    public function validate($dto, Constraint $constraint): void
    {
        // Validating against duplicate key should be done prior project creation, this is just 2nd line of defence just in case.
        $existingKey = $this->projectService->doesKeyAlreadyExistsByName($dto->getKey());
        if ($existingKey) {
            $this->context->buildViolation('Key should be unique.')
                ->atPath('key')
                ->addViolation();
        }
    }
}