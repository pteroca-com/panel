<?php

namespace App\Core\Form;

use App\Core\Entity\ProductPrice;
use App\Core\Enum\ProductPriceTypeEnum;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductPriceDynamicFormType extends AbstractPriceDynamicFormType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductPrice::class,
            'empty_data' => function () {
                return (new ProductPrice())
                    ->setType(ProductPriceTypeEnum::ON_DEMAND);
            },
        ]);
    }
}
