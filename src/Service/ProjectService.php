<?php

namespace App\Service;

use App\Entity;
use App\Enum\ProjectRoleEnum;
use App\Model\Dto\CreateProjectDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkflowService $workflowService,
    ) {    
    }

    public function create(CreateProjectDto $dto, Entity\User $user): Entity\Project
    {
        $project = new Entity\Project();

        $project->setCreatedBy($user);
        $project->setTitle($dto->getTitle());
        $project->setKey($dto->getKey());

        $template = $this->em->getRepository(Entity\ProjectTemplate::class)->find($dto->getTemplateId());
        if (null === $template) {
            throw new NotFoundHttpException('Project template not found.');
        }

        $project->setTemplate($template);

        $this->em->persist($project);
        $this->em->flush();

        $this->workflowService->createDefaultWorkflow($project);

        $projectUser = new Entity\ProjectUser();
        $projectUser->setProject($project);
        $projectUser->setUser($user);
        $projectUser->setEmail($user->getEmail());
        $projectUser->setRole(ProjectRoleEnum::ADMIN);

        $this->em->persist($projectUser);
        $this->em->flush();

        return $project;
    }

    public function doesKeyAlreadyExistsByName(string $keyValue): bool
    {
        $existingKey = $this->em->getRepository(Entity\Project::class)->findOneBy(['key' => $keyValue]);

        return null !== $existingKey;
    }
}
