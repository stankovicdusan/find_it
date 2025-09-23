<?php

namespace App\Controller\Dashboard\Comment;

use App\Entity\Ticket;
use App\Entity\TicketComment;
use App\Form\Type\TicketCommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class TicketCommentController extends AbstractController
{
    #[Route('/ticket/{id}/comment', name: 'ticket_comment_create', methods: ['POST'])]
    public function commentCreate(
        Request $request,
        Ticket $ticket,
        EntityManagerInterface $em,
    ): JsonResponse {
        $comment = new TicketComment();
        $form = $this->createForm(TicketCommentType::class, $comment, [
            'action' => $this->generateUrl('ticket_comment_create', ['id' => $ticket->getId()]),
            'method' => 'POST',
        ])->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse([
                'ok'   => false,
                'html' => $this->render('dashboard/tickets/comments/comment_form.html.twig', [
                    'form'   => $form->createView(),
                    'ticket' => $ticket,
                ]),
            ], 422);
        }

        $comment->setCreatedBy($this->getUser());
        $comment->setTicket($ticket);

        $em->persist($comment);
        $em->flush();

        $listHtml = $this->renderView('dashboard/tickets/comments/comments_list.html.twig', [
            'ticket' => $ticket,
        ]);

        $formHtml = $this->renderView('dashboard/tickets/comments/comment_form.html.twig', [
            'form'   => $this->createForm(TicketCommentType::class)->createView(),
            'ticket' => $ticket,
        ]);

        return new JsonResponse([
            'ok'      => true,
            'listHtml'=> $listHtml,
            'formHtml'=> $formHtml,
        ]);
    }

}