<?php

namespace App\Entity;

use App\Entity\Traits\Blameable;
use App\Entity\Traits\Deleteable;
use App\Entity\Traits\Timestampable;
use App\Entity\Traits\Uniqueable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ticket_comments')]
#[ORM\HasLifecycleCallbacks]
class TicketComment
{
    use Uniqueable, Timestampable, Deleteable, Blameable;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Ticket $ticket;

    #[ORM\Column(type: 'text')]
    private string $body;

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }

    public function setTicket(Ticket $ticket): void
    {
        $this->ticket = $ticket;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }
}
