<?php

namespace App\Core\Form;

use App\Core\Entity\ProductPrice;
use App\Core\Enum\ProductPriceTypeEnum;
use App\Core\Enum\ProductPriceUnitEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductPriceDynamicFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('value', IntegerType::class, [
                'label' => 'pteroca.crud.product.period',
                'required' => true,
                'attr' => [
                    'readonly' => true,
                ],
                'data' => 1,
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'pteroca.crud.product.unit',
                'choices' => ProductPriceUnitEnum::getChoices(),
                'required' => true,
                'disabled' => true,
                'attr' => [
                    'readonly' => true,
                ],
                'data' => ProductPriceUnitEnum::MINUTES,
            ])
            ->add('price', NumberType::class, [
                'label' => 'pteroca.crud.product.price',
                'scale' => 2,
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProductPrice::class,
            'empty_data' => function (FormInterface $form) {
                return (new ProductPrice())
                    ->setType(ProductPriceTypeEnum::ON_DEMAND);
            },
        ]);
    }
}