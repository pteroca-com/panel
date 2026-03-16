<?php

namespace App\Core\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class PluginUploadFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pluginFile', FileType::class, [
                'label' => 'pteroca.plugin.upload.file_label',
                'required' => true,
                'attr' => [
                    'accept' => '.zip',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '50M',
                        'mimeTypes' => [
                            'application/zip',
                            'application/x-zip-compressed',
                            'application/x-zip',
                            'application/octet-stream',
                        ],
                        'mimeTypesMessage' => 'pteroca.plugin.upload.invalid_mime_type',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'pteroca.plugin.upload.submit',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }
}
