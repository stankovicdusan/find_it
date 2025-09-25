<?php

namespace App\Form\Type;

use App\Entity\Sprint;
use App\Entity\Ticket;
use App\Enum\SprintStateEnum;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BacklogAddToSprintType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $project = $options['project'];

        $builder
            ->setMethod('POST')
            ->add('sprint', EntityType::class, [
                'label'         => 'Sprint',
                'class'         => Sprint::class,
                'choice_label'  => fn(Sprint $s) => sprintf('%s (%s → %s)',
                    $s->getName(),
                    $s->getPlannedStartAt()->format('d.m.Y'),
                    $s->getPlannedEndAt()->format('d.m.Y')
                ),
                'placeholder'   => 'Select sprint…',
                'query_builder' => function (EntityRepository $er) use ($project) {
                    return $er->createQueryBuilder('s')
                        ->andWhere('s.project = :p')->setParameter('p', $project)
                        ->andWhere('s.state = :st')->setParameter('st', SprintStateEnum::PLANNED)
                        ->orderBy('s.plannedStartAt', 'ASC');
                },
                'attr'          => [
                    'class' => 'form-control',
                ],
            ])
            ->add('tickets', EntityType::class, [
                'label'         => 'Backlog tickets',
                'class'         => Ticket::class,
                'multiple'      => true,
                'expanded'      => true,
                'choice_label'  => function (Ticket $t) {
                    $key = $t->getStatus()->getWorkflow()->getProject()->getKey();
                    return sprintf('%s-%d — %s', $key, $t->getIndexNumber(), $t->getTitle());
                },
                'query_builder' => function (EntityRepository $er) use ($project) {
                    return $er->createQueryBuilder('t')
                        ->leftJoin('t.sprint', 'sp')
                        ->innerJoin('t.status', 'st')
                        ->innerJoin('st.workflow', 'wf')
                        ->innerJoin('wf.project', 'p')
                        ->andWhere('p = :project')->setParameter('project', $project)
                        ->andWhere('sp IS NULL')
                        ->orderBy('t.updatedAt', 'DESC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('project')
            ->setDefaults([
                'csrf_protection' => true,
                'csrf_token_id'   => 'backlog_add',
            ])
        ;
    }
}