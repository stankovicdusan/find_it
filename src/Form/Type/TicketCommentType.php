<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TicketCommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('body', TextareaType::class, [
            'label' => false,
            'required' => false,
            'attr' => [
                'rows'        => 3,
                'placeholder' => 'Write a comment…',
                'class'       => 'form-control',
            ],
            'constraints' => [
                new Assert\NotBlank(message: 'Comment cannot be empty.'),
                new Assert\Length(max: 2000),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'POST',
        ]);
    }
}
