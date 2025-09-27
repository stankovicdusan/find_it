<?php

namespace App\Form\Type;

use App\Entity\ProjectUser;
use App\Enum\MemberStatusEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\IssueType;
use App\Entity\Project;
use App\Entity\User;
use App\Entity\Ticket;
use App\Enum\PriorityEnum;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Project $project */
        $project = $options['project'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'Summary',
                'attr'  => [
                    'class' => 'form-control',
                ],
            ])
            ->add('issueType', EntityType::class, [
                'class'        => IssueType::class,
                'choice_label' => 'title',
                'label'        => 'Issue Type',
                'attr'         => [
                    'class' => 'form-select',
                ],
            ])
            ->add('priority', EnumType::class, [
                'label'   => 'Priority',
                'class'   => PriorityEnum::class,
                'choice_label' => fn (PriorityEnum $choice) => ucfirst($choice->value),
                'attr'    => [
                    'class' => 'form-select',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr'  => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
            ])
            ->add('assignedTo', EntityType::class, [
                'class'        => User::class,
                'choice_label' => 'email',
                'label'        => 'Assignee',
                'placeholder'  => 'Unassigned',
                'required'     => false,
                'attr'         => [
                    'class' => 'form-select',
                ],
                'query_builder' => function (EntityRepository $er) use ($project) {
                    return $er->createQueryBuilder('u')
                        ->andWhere('u IN (
                            SELECT pu_user FROM App\Entity\ProjectUser pu
                            JOIN pu.user pu_user
                            WHERE pu.project = :project
                              AND pu.status = :active
                        )')
                        ->setParameter('project', $project)
                        ->setParameter('active', MemberStatusEnum::ACTIVE)
                        ->orderBy('u.email', 'ASC');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'method'     => 'POST',
            'project'    => null,
        ]);
    }
}