<?php

namespace App\Validator;

use App\Entity\WorkflowStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

class WorkflowStatusValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * @param WorkflowStatus $status
     */
    public function validate(mixed $status, Constraint $constraint): void
    {
        if (!$status instanceof WorkflowStatus) {
            return;
        }


        dump($status->getTransitions());
        $title = $status->getTitle();
        $workflow = $status->getWorkflow();

        $title = mb_strtolower(trim($title));
        $exists = $this->em->getRepository(WorkflowStatus::class)->checkStatusTitleUniqueness($workflow, $title, $status->getId());

        if ($exists) {
            $this->context
                ->buildViolation('Status with such title already exists in this project.')
                ->atPath('title')
                ->addViolation();
        }
    }
}
