<?php

namespace App\Core\Form;

use App\Core\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ResetPasswordFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('newPassword', PasswordType::class, [
                'label' => $this->translator->trans('pteroca.recovery.new_password'),
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('pteroca.recovery.enter_new_password'),
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => $this->translator->trans('pteroca.recovery.at_least_limit_characters'),
                        'max' => 4096,
                    ]),
                ],
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => $this->translator->trans('pteroca.recovery.confirm_new_password'),
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('pteroca.recovery.please_confirm_password'),
                    ]),
                    new Callback([$this, 'validatePasswordMatch'])
                ],
                'attr' => ['autocomplete' => 'new-password'],
            ]);
    }

    public function validatePasswordMatch($object, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        $newPassword = $form->get('newPassword')->getData();
        $confirmPassword = $form->get('confirmPassword')->getData();

        if ($newPassword !== $confirmPassword) {
            $context
                ->buildViolation($this->translator->trans('pteroca.recovery.passwords_must_match'))
                ->atPath('confirmPassword')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $disableCsrf = isset($_ENV['DISABLE_CSRF']) && $_ENV['DISABLE_CSRF'] === 'true';
        $resolver->setDefaults([
            'csrf_protection' => !$disableCsrf,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'user_registration',
        ]);
    }
}
