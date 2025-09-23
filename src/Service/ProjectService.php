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

        $projectMember = new Entity\ProjectMember();
        $projectMember->setProject($project);
        $projectMember->setUser($user);
        $projectMember->setEmail($user->getEmail());
        $projectMember->setRole(ProjectRoleEnum::ADMIN);

        $this->em->persist($projectMember);
        $this->em->flush();

        return $project;
    }

    public function doesKeyAlreadyExistsByName(string $keyValue): bool
    {
        $existingKey = $this->em->getRepository(Entity\Project::class)->findOneBy(['key' => $keyValue]);

        return null !== $existingKey;
    }
}
