<?php

namespace App\Controller\Project;

use App\Controller\BaseController;
use App\Entity\ProjectMember;
use App\Enum\MemberStatusEnum;
use App\Repository\ProjectMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/invites')]
#[IsGranted("ROLE_USER")]
class InviteController extends BaseController
{
    #[Route('/count', name: 'invites_count', methods: ['GET'])]
    public function count(
        EntityManagerInterface $em,
    ): JsonResponse {
        $email = strtolower($this->getLoggedInUser()->getEmail());
        $count = $em->getRepository(ProjectMember::class)->countPendingForEmail($email);

        return new JsonResponse(['count' => $count]);
    }

    #[Route('/list', name: 'invites_list', methods: ['GET'])]
    public function list(
        EntityManagerInterface $em,
    ): Response {
        $email = strtolower($this->getLoggedInUser()->getEmail());
        $invites = $em->getRepository(ProjectMember::class)->findPendingForEmail($email);

        return $this->render('projects/invites_list.html.twig', [
            'invites' => $invites,
        ]);
    }

    #[Route('/accept/{id}', name: 'invite_accept', methods: ['POST'])]
    public function accept(
        ProjectMember $pm,
        Request $request,
        EntityManagerInterface $em,
    ): JsonResponse {
        $user  = $this->getLoggedInUser();
        $email = strtolower($user->getEmail());

        if (!$this->isCsrfTokenValid('invite_act_' . $pm->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['ok' => false, 'message' => 'Invalid CSRF'], 403);
        }

        if ($pm->getStatus() !== MemberStatusEnum::INVITED || strtolower($pm->getEmail()) !== $email) {
            return new JsonResponse(['ok' => false, 'message' => 'Invite not valid for this account'], 422);
        }

        $pm->setUser($user);
        $pm->setStatus(MemberStatusEnum::ACTIVE);

        $em->flush();

        $invites = $em->getRepository(ProjectMember::class)->findPendingForEmail($email);
        $html = $this->renderView('projects/invites_list.html.twig', ['invites' => $invites]);
        $count = $em->getRepository(ProjectMember::class)->countPendingForEmail($email);

        return new JsonResponse(['ok' => true, 'html' => $html, 'count' => $count]);
    }

    #[Route('/decline/{id}', name: 'invite_decline', methods: ['POST'])]
    public function decline(
        ProjectMember $pm,
        Request $request,
        EntityManagerInterface $em,
    ): JsonResponse {
        $user  = $this->getLoggedInUser();
        $email = strtolower($user->getEmail());

        if (!$this->isCsrfTokenValid('invite_act_' . $pm->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['ok' => false, 'message' => 'Invalid CSRF'], 403);
        }

        if ($pm->getStatus() !== MemberStatusEnum::INVITED || strtolower($pm->getEmail()) !== $email) {
            return new JsonResponse(['ok' => false, 'message' => 'Invite not valid for this account'], 422);
        }

        $em->remove($pm);
        $em->flush();

        $invites = $em->getRepository(ProjectMember::class)->findPendingForEmail($email);
        $html = $this->renderView('projects/invites_list.html.twig', ['invites' => $invites]);
        $count = $em->getRepository(ProjectMember::class)->countPendingForEmail($email);

        return new JsonResponse(['ok' => true, 'html' => $html, 'count' => $count]);
    }
}
