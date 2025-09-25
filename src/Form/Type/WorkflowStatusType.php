<?php

namespace App\Form\Type;

use App\Entity\WorkflowStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
                'label'    => 'Set as initial status',
            ])
            ->add('isFinal', CheckboxType::class, [
                'required' => false,
                'label'    => 'Set as final status',
            ]);

        if (!empty($transitionChoices)) {
            $builder->add('transitions', ChoiceType::class, [
                'choices'      => $transitionChoices,
                'multiple'     => true,
                'required'     => false,
                'expanded'     => true,
                'mapped'       => false,
                'choice_label' => fn(WorkflowStatus $status) => $status->getTitle(),
                'label'        => 'Select allowed transitions:',
                'data'         => $options['preselected_transitions'],
            ]);
        } else {
            $builder->add('no_transitions_info', TextareaType::class, [
                'mapped' => false,
                'data' => 'No transitions available. Please add status to see options.',
                'attr' => [
                    'readonly' => true,
                    'class' => 'form-control-plaintext text-muted',
                    'rows' => 2,
                ],
                'label' => false,
            ]);
        }

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var WorkflowStatus $status */
            $status = $event->getForm()->getData();
            $form   = $event->getForm();

            if ($status->isInitial() && $status->isFinal()) {
                if ($form->has('isFinal')) {
                    $form->get('isFinal')->addError(new FormError('A status cannot be both initial and final.'));
                } else {
                    $form->addError(new FormError('A status cannot be both initial and final.'));
                }
            }

            // don't require transitions if status is final
            if ($form->has('transitions')) {
                $selected = (array) $form->get('transitions')->getData();
                if (!$status->isFinal() && count($selected) < 1) {
                    $form->get('transitions')->addError(new FormError('Select at least one transition.'));
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'              => WorkflowStatus::class,
            'method'                  => 'POST',
            'available_statuses'      => [],
            'preselected_transitions' => [],
        ]);
    }
}
