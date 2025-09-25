<?php

namespace App\Form\Type;

use App\Entity\Sprint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SprintStartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setMethod('POST');

        /** @var Sprint|null $sprint */
        $sprint = $options['sprint'] ?? null;

        $showShift = false;
        if ($sprint) {
            $today    = (new \DateTimeImmutable('today'))->setTime(0, 0);
            $startDay = $sprint->getPlannedStartAt()->setTime(0, 0);
            $endDay   = $sprint->getPlannedEndAt()->setTime(0, 0);

            $isLate    = $today > $startDay;
            $isOverdue = $today > $endDay;

            $showShift = $isLate || $isOverdue;
        }

        if ($showShift) {
            $builder->add('shiftWindow', CheckboxType::class, [
                'label'    => 'Shift planned window to start today (keep duration)',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_token_id'   => 'sprint_start',
            'sprint'          => null,
        ]);

        $resolver->setAllowedTypes('sprint', ['null', Sprint::class]);
    }
}