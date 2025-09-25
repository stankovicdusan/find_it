<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\Sprint;
use App\Entity\Ticket;
use App\Enum\SprintStateEnum;
use Doctrine\ORM\EntityManagerInterface;

class SprintService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function createPlanned(Project $project, Sprint $sprint): Sprint
    {
        if ($sprint->getPlannedStartAt() >= $sprint->getPlannedEndAt()) {
            throw new \InvalidArgumentException('Start must be before end.');
        }

        $sprint->setProject($project);
        $sprint->setState(SprintStateEnum::PLANNED);

        $this->em->persist($sprint);
        $this->em->flush();

        return $sprint;
    }

    public function shiftPlannedWindowToNow(Sprint $sprint): void
    {
        $duration = $sprint->getPlannedEndAt()->getTimestamp() - $sprint->getPlannedStartAt()->getTimestamp();

        if ($duration < 3600) $duration = 3600;
        $now = new \DateTimeImmutable('now');

        $sprint->setPlannedStartAt($now);
        $sprint->setPlannedEndAt($now->modify('+' . $duration . ' seconds'));

        $this->em->flush();
    }

    public function startSprint(Sprint $sprint): void
    {
        $active = $this->em->getRepository(Sprint::class)->findActiveForProject($sprint->getProject());
        if ($active && $active->getId() !== $sprint->getId()) {
            throw new \RuntimeException('There is already an active sprint in this project.');
        }

        $tickets = $this->em->getRepository(Ticket::class)->findBySprint($sprint);
        if (\count($tickets) < 1) {
            throw new \RuntimeException('Sprint must contain at least one ticket before starting.');
        }

        $sprint->setState(SprintStateEnum::ACTIVE);
        $sprint->setStartedAt(new \DateTimeImmutable('now'));

        $this->em->flush();
    }

    public function completeSprint(Sprint $sprint, ?Sprint $moveNotDoneTo = null): void
    {
        if ($sprint->getState() !== SprintStateEnum::ACTIVE) {
            throw new \RuntimeException('Only an active sprint can be completed.');
        }

        $tickets = $this->em->getRepository(Ticket::class)->findBySprint($sprint);
        $notDone = [];
        foreach ($tickets as $t) {
            if (!$t->getStatus()->isFinal()) $notDone[] = $t;
        }

        if ($notDone) {
            if ($moveNotDoneTo
                && $moveNotDoneTo->getProject()->getId() === $sprint->getProject()->getId()
                && $moveNotDoneTo->getState() === SprintStateEnum::PLANNED) {
                foreach ($notDone as $t) {
                    $t->setSprint($moveNotDoneTo);
                }
            } else {
                foreach ($notDone as $t) {
                    $t->setSprint(null);
                }
            }
        }

        $sprint->setState(SprintStateEnum::COMPLETED);
        $sprint->setCompletedAt(new \DateTimeImmutable('now'));

        $this->em->flush();
    }

    public function addTicketsToSprint(Sprint $sprint, array $tickets): void
    {
        foreach ($tickets as $ticket) {
            $projOfTicket = $ticket->getStatus()->getWorkflow()->getProject();
            if ($projOfTicket->getId() !== $sprint->getProject()->getId()) {
                throw new \RuntimeException('Ticket and sprint must belong to the same project.');
            }

            $ticket->setSprint($sprint);
        }

        $this->em->flush();
    }
}
