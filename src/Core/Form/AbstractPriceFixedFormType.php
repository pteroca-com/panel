<?php

namespace App\Core\Form;

use App\Core\Enum\ProductPriceUnitEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

abstract class AbstractPriceFixedFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('value', IntegerType::class, [
                'label' => 'pteroca.crud.product.period',
                'required' => true,
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'pteroca.crud.product.unit',
                'choices' => ProductPriceUnitEnum::getChoices(),
                'required' => true,
            ])
            ->add('price', NumberType::class, [
                'label' => 'pteroca.crud.product.price',
                'scale' => 2,
                'required' => true,
            ])
            ->add('hasFreeTrial', ChoiceType::class, [
                'label' => 'pteroca.crud.product.has_free_trial',
                'choices' => [
                    'Yes' => '1',
                    'No' => '0',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ])
            ->add('freeTrialValue', IntegerType::class, [
                'label' => 'pteroca.crud.product.free_trial_value',
                'required' => false,
            ])
            ->add('freeTrialUnit', ChoiceType::class, [
                'label' => 'pteroca.crud.product.free_trial_unit',
                'choices' => ProductPriceUnitEnum::getChoices(),
                'required' => true,
            ]);
    }
}
