<?php

namespace App\Core\Form;

use App\Core\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translations = [
            'email_address' => $this->translator->trans('pteroca.register.email_address'),
            'accept_terms' => $this->translator->trans('pteroca.register.accept_terms'),
            'please_enter_password' => $this->translator->trans('pteroca.register.please_enter_password'),
            'password_at_least_limit_characters' => $this->translator->trans('pteroca.register.password_at_least_limit_characters', ['{{ limit }}' => 6]),
            'should_accept_terms' => $this->translator->trans('pteroca.register.should_accept_terms'),
            'please_enter_name' => $this->translator->trans('pteroca.register.please_enter_name'),
            'name_at_least_limit_characters' => $this->translator->trans('pteroca.register.name_at_least_limit_characters', ['{{ limit }}' => 2]),
            'please_enter_surname' => $this->translator->trans('pteroca.register.please_enter_surname'),
            'surname_at_least_limit_characters' => $this->translator->trans('pteroca.register.surname_at_least_limit_characters', ['{{ limit }}' => 2]),
            'email_required' => $this->translator->trans('pteroca.register.email_required'),
            'invalid_email' => $this->translator->trans('pteroca.register.invalid_email'),
        ];

        $builder
            ->add('email', EmailType::class, [
                'label' => $translations['email_address'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => $translations['email_required']]),
                    new Length(['max' => 180]),
                    new Email(['message' => $translations['invalid_email']]),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => $translations['email_address'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => $translations['please_enter_name']]),
                    new Length([
                        'min' => 2,
                        'minMessage' => $translations['name_at_least_limit_characters'],
                        'max' => 255,
                    ]),
                ],
            ])
            ->add('surname', TextType::class, [
                'label' => $translations['please_enter_surname'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => $translations['please_enter_surname']]),
                    new Length([
                        'min' => 2,
                        'minMessage' => $translations['surname_at_least_limit_characters'],
                        'max' => 255,
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $translations['please_enter_password'],
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(['message' => $translations['please_enter_password']]),
                    new Length([
                        'min' => 6,
                        'minMessage' => $translations['password_at_least_limit_characters'],
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => $translations['accept_terms'],
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new IsTrue([
                        'message' => $translations['should_accept_terms'],
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $disableCsrf = isset($_ENV['DISABLE_CSRF']) && $_ENV['DISABLE_CSRF'] === 'true';
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => !$disableCsrf,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'user_registration',
        ]);
    }
}
