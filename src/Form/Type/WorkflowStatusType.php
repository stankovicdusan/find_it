<?php

namespace App\Form\Type;

use App\Entity\WorkflowStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WorkflowStatus[] $transitionChoices */
        $transitionChoices = $options['available_statuses'] ?? [];

        $builder
            ->add('title', TextType::class, [
                'attr'  => [
                    'class' => 'form-control',
                ],
            ])
            ->add('isInitial', CheckboxType::class, [
                'required' => false,
                'label' => 'Set as initial status',
            ])
            ->add('transitions', ChoiceType::class, [
                'mapped'       => false,
                'choices'      => $transitionChoices,
                'choice_label' => fn($status) => $status->getTitle(),
                'choice_value' => 'id',
                'multiple'     => true,
                'expanded'     => true,
                'required'     => false,
                'label'        => 'Allowed transitions to:',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => WorkflowStatus::class,
            'available_statuses' => [],
        ]);
    }
}
