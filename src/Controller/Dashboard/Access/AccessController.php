<?php

namespace App\Controller\Dashboard\Access;

use App\Entity;
use App\Controller\BaseController;
use App\Enum\MemberStatusEnum;
use App\Enum\ProjectRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/{key}', name: 'dashboard_')]
#[IsGranted('PROJECT_ADMIN', subject: 'project')]
class AccessController extends BaseController
{
    #[Route('/access', name: 'access', methods: ['GET'])]
    public function index(
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
    ): Response {
        return new Response(
            $this->renderView('dashboard/access/index.html.twig', [
                'project'    => $project,
                'activeMenu' => 'access',
            ]),
        );
    }

    #[Route('/access/list', name: 'access_list', methods: ['GET'])]
    public function list(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        EntityManagerInterface $em,
    ): Response {
        $q = trim((string) $request->query->get('q',''));
        $members = $em->getRepository(Entity\ProjectUser::class)->searchByProject($project, $q);

        return new Response(
            $this->renderView('dashboard/access/members_table.html.twig', [
                'project' => $project,
                'members' => $members,
            ])
        );
    }

    #[Route('/access/invite', name: 'access_invite', methods: ['POST'])]
    public function invite(
        Request $request,
        #[MapEntity(mapping: ['key'=>'key'])] Entity\Project $project,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('invite_member_' . $project->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['ok' => false, 'message' => 'Invalid CSRF'], 403);
        }

        $email = trim((string) $request->request->get('email', ''));
        $role = in_array($request->request->get('role'), ProjectRoleEnum::cases()) ? $request->request->get('role') : ProjectRoleEnum::MEMBER->value;
        $role = ProjectRoleEnum::fromString($role);

        if ('' === $email) {
            return new JsonResponse(['ok' => false, 'message' => 'Email required'], 422);
        }

        if ($em->getRepository(Entity\ProjectUser::class)->memberExists($project, $email)) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'This email is already added.'
            ], 422);
        }

        $pm = new Entity\ProjectUser();

        $pm->setProject($project);
        $pm->setEmail($email);
        $pm->setRole($role);

        if ($user = $em->getRepository(Entity\User::class)->findOneBy(['email' => $email])) {
            $pm->setUser($user);
            $pm->setStatus(MemberStatusEnum::ACTIVE);
        } else {
            $pm->setStatus(MemberStatusEnum::INVITED);
        }

        $em->persist($pm);
        $em->flush();

        $html = $this->renderView('dashboard/access/members_table.html.twig', [
            'project' => $project,
            'members' => $em->getRepository(Entity\ProjectUser::class)->searchByProject($project, ''),
        ]);

        return new JsonResponse([
            'ok'   => true,
            'html' => $html,
        ]);
    }

    #[Route('/access/role/{member}', name: 'access_role', methods: ['POST'])]
    public function roles(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        Entity\ProjectUser $member,
        EntityManagerInterface $em,
    ): JsonResponse {
        if ($member->getProject()->getId() !== $project->getId()) {
            return new JsonResponse(['ok' => false], 404);
        }

        if (!$this->isCsrfTokenValid('member_action_' . $member->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['ok' => false, 'message' => 'Invalid CSRF'], 403);
        }

        $role = $request->request->get('role');
        $role = ProjectRoleEnum::fromString($role);
        if (!in_array($role, ProjectRoleEnum::cases())) {
            return new JsonResponse(['ok' => false], 422);
        }

        if (ProjectRoleEnum::ADMIN === $member->getRole() && ProjectRoleEnum::MEMBER === $role && $em->getRepository(Entity\ProjectUser::class)->countAdmins($project) <= 1) {
            return new JsonResponse(['ok' => false, 'message' => 'At least one admin required'], 422);
        }

        $member->setRole($role);
        $em->flush();

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/access/remove/{member}', name: 'access_remove', methods: ['POST'])]
    public function remove(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        Entity\ProjectUser $member,
        EntityManagerInterface $em,
    ): JsonResponse {
        if ($member->getProject()->getId() !== $project->getId()) {
            return new JsonResponse(['ok' => false], 404);
        }

        if (!$this->isCsrfTokenValid('member_action_' . $member->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['ok' => false, 'message' => 'Invalid CSRF'], 403);
        }

        if ($member->getUser() && $this->getUser() && $member->getUser()->getId() === $this->getUser()->getId()) {
            return new JsonResponse(['ok' => false, 'message' => "You cannot remove yourself from this project."], 422);
        }

        if (ProjectRoleEnum::ADMIN === $member->getRole() && $em->getRepository(Entity\ProjectUser::class)->countAdmins($project) <= 1) {
            return new JsonResponse(['ok' => false, 'message' => 'At least one admin required'], 422);
        }

        $em->remove($member);
        $em->flush();

        $html = $this->renderView('dashboard/access/members_table.html.twig', [
            'project' => $project,
            'members' => $em->getRepository(Entity\ProjectUser::class)->searchByProject($project, ''),
        ]);

        return new JsonResponse(['ok' => true, 'html' => $html]);
    }
}
