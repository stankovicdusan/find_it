<?php

namespace App\Entity;

use App\Entity\Traits\Uniqueable;
use App\Enum\MemberStatusEnum;
use App\Repository\ProjectUserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectUserRepository::class)]
#[ORM\Table(name: 'project_users')]
#[ORM\UniqueConstraint(columns: ['project_id', 'email'])]
class ProjectUser
{
    use Uniqueable;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Role $role;

    #[ORM\Column(length: 190)]
    private string $email;

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

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): void
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
