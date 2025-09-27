<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\Role;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\MemberStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

class AccessService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Environment $twig,
        private readonly CsrfTokenManagerInterface $csrf,
    ) {}

    public function renderMembersTable(Project $project, string $q = ''): string
    {
        $members = $this->em->getRepository(ProjectUser::class)->searchByProject($project, trim($q));

        return $this->twig->render('dashboard/access/members_table.html.twig', [
            'project' => $project,
            'members' => $members,
            'roles'   => $this->em->getRepository(Role::class)->findAll(),
        ]);
    }

    public function invite(
        Project $project,
        string $email,
        string $roleId,
        string $csrfToken,
    ): array {
        // CSRF
        if (!$this->isValidCsrf('invite_member_' . $project->getId(), $csrfToken)) {
            return ['ok' => false, 'message' => 'Invalid CSRF', 'code' => 403];
        }

        $email = trim($email);
        if ($email === '') {
            return ['ok' => false, 'message' => 'Email required', 'code' => 422];
        }

        if ($this->em->getRepository(ProjectUser::class)->memberExists($project, $email)) {
            return ['ok' => false, 'message' => 'This email is already added.', 'code' => 422];
        }

        $pu = new ProjectUser();

        $pu->setProject($project);
        $pu->setEmail($email);

        $role = $this->em->getRepository(Role::class)->find($roleId);
        $pu->setRole($role);

        /** @var User|null $user */
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user) {
            $pu->setUser($user);
            $pu->setStatus(MemberStatusEnum::ACTIVE);
        } else {
            $pu->setStatus(MemberStatusEnum::INVITED);
        }

        $this->em->persist($pu);
        $this->em->flush();

        return [
            'ok'   => true,
            'html' => $this->renderMembersTable($project),
        ];
    }

    public function changeRole(
        Project $project,
        ProjectUser $member,
        string $roleId,
        string $csrfToken,
    ): array {
        if ($member->getProject()->getId() !== $project->getId()) {
            return ['ok' => false, 'message' => 'Not found', 'code' => 404];
        }

        if (!$this->isValidCsrf('member_action_' . $member->getId(), $csrfToken)) {
            return ['ok' => false, 'message' => 'Invalid CSRF', 'code' => 403];
        }

        $role = $this->em->getRepository(Role::class)->find($roleId);
        $member->setRole($role);

        $this->em->flush();

        return ['ok' => true];
    }

    public function remove(
        Project $project,
        ProjectUser $member,
        User $user,
        string $csrfToken
    ): array {
        if ($member->getProject()->getId() !== $project->getId()) {
            return ['ok' => false, 'message' => 'Not found', 'code' => 404];
        }

        if (!$this->isValidCsrf('member_action_' . $member->getId(), $csrfToken)) {
            return ['ok' => false, 'message' => 'Invalid CSRF', 'code' => 403];
        }

        if ($member->getUser() && $member->getUser()->getId() === $user->getId()) {
            return ['ok' => false, 'message' => 'You cannot remove yourself from this project.', 'code' => 422];
        }

        $this->reassignTickets($member->getUser(), $member->getProject());

        $this->em->remove($member);
        $this->em->flush();

        return [
            'ok'   => true,
            'html' => $this->renderMembersTable($project),
        ];
    }

    private function reassignTickets(User $user, Project $project): void
    {
        $tickets = $this->em->getRepository(Ticket::class)->getUserTicketsFromProject($user, $project);

        foreach ($tickets as $ticket) {
            $ticket->setAssignedTo(null);
        }
    }

    private function isValidCsrf(string $id, string $token): bool
    {
        return $this->csrf->isTokenValid(new CsrfToken($id, $token));
    }
}
