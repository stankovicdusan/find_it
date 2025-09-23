<?php

namespace App\Entity;

use App\Entity\Traits\Uniqueable;
use App\Enum\MemberStatusEnum;
use App\Enum\ProjectRoleEnum;
use App\Repository\ProjectMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectMemberRepository::class)]
#[ORM\Table(name: 'project_members')]
#[ORM\UniqueConstraint(columns: ['project_id', 'email'])]
class ProjectMember
{
    use Uniqueable;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 190)]
    private string $email;

    #[ORM\Column(type: 'string', length: 20, enumType: ProjectRoleEnum::class)]
    private ProjectRoleEnum $role = ProjectRoleEnum::MEMBER;

    #[ORM\Column(type: 'string', length: 20, enumType: MemberStatusEnum::class)]
    private MemberStatusEnum $status = MemberStatusEnum::ACTIVE;

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): void
    {
        $this->project = $project;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getRole(): ProjectRoleEnum
    {
        return $this->role;
    }

    public function setRole(ProjectRoleEnum $role): void
    {
        $this->role = $role;
    }

    public function getStatus(): MemberStatusEnum
    {
        return $this->status;
    }

    public function setStatus(MemberStatusEnum $status): void
    {
        $this->status = $status;
    }
}
