<?php

namespace App\Controller\Dashboard\Access;

use App\Entity;
use App\Controller\BaseController;
use App\Service\AccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/{key}/access', name: 'dashboard_')]
#[IsGranted('PROJECT_ADMIN', subject: 'project')]
class AccessController extends BaseController
{
    #[Route('', name: 'access', methods: ['GET'])]
    public function index(
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        EntityManagerInterface $em,
    ): Response {
        return new Response(
            $this->renderView('dashboard/access/index.html.twig', [
                'project'    => $project,
                'activeMenu' => 'access',
                'roles'      => $em->getRepository(Entity\Role::class)->findAll(),
            ]),
        );
    }

    #[Route('/list', name: 'access_list', methods: ['GET'])]
    public function list(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        AccessService $accessService,
    ): Response {
        $q = trim((string) $request->query->get('q',''));
        $html = $accessService->renderMembersTable($project, $q);

        return new Response($html);
    }

    #[Route('/invite', name: 'access_invite', methods: ['POST'])]
    public function invite(
        Request $request,
        #[MapEntity(mapping: ['key'=>'key'])] Entity\Project $project,
        AccessService $accessService,
    ): JsonResponse {
        $email = (string) $request->request->get('email', '');
        $role  = (string) $request->request->get('role', '');
        $csrf  = (string) $request->request->get('_token', '');

        $res = $accessService->invite($project, $email, $role, $csrf);
        $code = $res['code'] ?? ($res['ok'] ? 200 : 422);

        return new JsonResponse($res, $code);
    }

    #[Route('/role/{member}', name: 'access_role', methods: ['POST'])]
    public function roles(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        Entity\ProjectUser $member,
        AccessService $accessService,
    ): JsonResponse {
        $role = (string) $request->request->get('role', '');
        $csrf = (string) $request->request->get('_token', '');

        $res  = $accessService->changeRole($project, $member, $role, $csrf);
        $code = $res['code'] ?? ($res['ok'] ? 200 : 422);

        return new JsonResponse($res, $code);
    }

    #[Route('/remove/{member}', name: 'access_remove', methods: ['POST'])]
    public function remove(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        Entity\ProjectUser $member,
        AccessService $accessService,
    ): JsonResponse {
        $csrf = (string) $request->request->get('_token', '');

        $res  = $accessService->remove($project, $member, $this->getLoggedInUser(), $csrf);
        $code = $res['code'] ?? ($res['ok'] ? 200 : 422);

        return new JsonResponse($res, $code);
    }
}
