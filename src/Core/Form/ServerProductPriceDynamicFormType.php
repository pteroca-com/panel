<?php

namespace App\Core\Form;

use App\Core\Entity\ServerProductPrice;
use App\Core\Enum\ProductPriceTypeEnum;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServerProductPriceDynamicFormType extends AbstractPriceDynamicFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('is_selected', ChoiceType::class, [
            'label' => 'pteroca.crud.product.is_selected',
            'choices' => [
                'pteroca.crud.product.yes' => true,
                'pteroca.crud.product.no' => false,
            ],
            'expanded' => true,
            'multiple' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ServerProductPrice::class,
            'empty_data' => function (FormInterface $form) {
                return (new ServerProductPrice())
                    ->setType(ProductPriceTypeEnum::ON_DEMAND);
            },
        ]);
    }
}
