<?php

namespace App\Core\Form;

use App\Core\Entity\ServerProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServerProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('price')
            ->add('cpu')
            ->add('memory')
            ->add('diskSpace')
            ->add('io')
            ->add('dbCount')
            ->add('originalProduct')
            ->add('swap')
            ->add('backups')
            ->add('ports')
            ->add('nodes')
            ->add('nest')
            ->add('eggs')
            ->add('eggsConfiguration')
            ->add('allowChangeEgg')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ServerProduct::class,
        ]);
    }
}
