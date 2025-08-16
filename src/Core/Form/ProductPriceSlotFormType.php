<?php

namespace App\Core\Form;

use App\Core\Entity\ProductPrice;
use App\Core\Enum\ProductPriceTypeEnum;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductPriceSlotFormType extends AbstractPriceFixedFormType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProductPrice::class,
            'empty_data' => function (FormInterface $form) {
                return (new ProductPrice())
                    ->setType(ProductPriceTypeEnum::SLOT);
            },
        ]);
    }
}
