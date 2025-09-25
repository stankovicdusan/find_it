<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class SprintCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod('POST')
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => [
                    'class' => 'form-control mb-3',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Name is required.'),
                    new Assert\Length(max: 120),
                ],
            ])
            ->add('goal', TextType::class, [
                'label' => 'Goal (optional)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control mb-3',
                ],
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 500)],
            ])
            ->add('plannedStartAt', DateTimeType::class, [
                'label' => 'Planned start',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-3',
                ],
                'input'  => 'datetime_immutable',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('plannedEndAt', DateTimeType::class, [
                'label' => 'Planned end',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-3',
                ],
                'input'  => 'datetime_immutable',
                'constraints' => [new Assert\NotBlank()],
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form  = $event->getForm();
            $start = $form->get('plannedStartAt')->getData();
            $end   = $form->get('plannedEndAt')->getData();

            if ($start instanceof \DateTimeInterface && $end instanceof \DateTimeInterface && $start >= $end) {
                $form->get('plannedEndAt')->addError(new FormError('End must be after start.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_token_id'   => 'sprint_create',
        ]);
    }
}
