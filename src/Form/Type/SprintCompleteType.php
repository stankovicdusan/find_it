<?php
// src/Form/SprintCompleteType.php
namespace App\Form\Type;

use App\Entity\Sprint;
use App\Enum\SprintStateEnum;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class SprintCompleteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $project = $options['project'];

        $builder->setMethod('POST');
        $builder->add('incompleteStrategy', ChoiceType::class, [
            'label'    => 'Incomplete tickets',
            'expanded' => true,
            'choices'  => [
                'Move to backlog'             => 'backlog',
                'Move to next planned sprint' => 'next',
            ],
            'constraints' => [new Assert\NotBlank()],
            'data' => 'backlog',
        ]);

        $builder->add('nextSprint', EntityType::class, [
            'label'        => 'Next sprint (optional)',
            'class'        => Sprint::class,
            'required'     => false,
            'placeholder'  => 'Select planned sprint…',
            'choice_label' => fn(Sprint $s) => sprintf('%s (%s → %s)',
                $s->getName(),
                $s->getPlannedStartAt()->format('d.m.Y'),
                $s->getPlannedEndAt()->format('d.m.Y')
            ),
            'query_builder' => function (EntityRepository $er) use ($project) {
                return $er->createQueryBuilder('s')
                    ->andWhere('s.project = :p')->setParameter('p', $project)
                    ->andWhere('s.state = :st')->setParameter('st', SprintStateEnum::PLANNED)
                    ->orderBy('s.plannedStartAt', 'ASC');
            },
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('project');
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_token_id'   => 'sprint_complete',
        ]);
    }
}
