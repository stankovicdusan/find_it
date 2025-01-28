<?php

namespace App\Controller\Dashboard;

use App\Controller\BaseController;
use App\Entity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

class DashboardController extends BaseController
{
    #[Route(path: '/dashboard/{key}', name: 'dashboard_index', methods: ["GET"])]
    public function index(
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
    ): Response {
        return $this->render('dashboard/board/index.html.twig', [
            'project' => $project,
        ]);
    }
}