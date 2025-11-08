<?php

namespace App\Core\Form;

use App\Core\Event\Form\FormBuildEvent;
use App\Core\Service\Event\EventContextService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginFormType extends AbstractType
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly EventContextService $eventContextService,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translations = [
            'email_address' => $this->translator->trans('pteroca.login.email_address'),
            'password' => $this->translator->trans('pteroca.login.password'),
            'remember_me' => $this->translator->trans('pteroca.login.remember_me'),
        ];

        $builder
            ->add('email', EmailType::class, [
                'label' => $translations['email_address'],
                'attr' => [
                    'placeholder' => 'pteroca.login.email_placeholder',
                    'autocomplete' => 'username',
                    'autofocus' => true,
                ],
                'label_attr' => [
                    'label_icon' => 'fas fa-envelope',
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => $translations['password'],
                'attr' => [
                    'placeholder' => 'pteroca.login.password_placeholder',
                    'autocomplete' => 'current-password',
                    'show_toggle_password' => true,
                ],
                'label_attr' => [
                    'label_icon' => 'fas fa-lock',
                ],
            ])
            ->add('remember_me', CheckboxType::class, [
                'label' => $translations['remember_me'],
                'required' => false,
                'mapped' => false,
            ])
        ;

        $request = $this->requestStack->getCurrentRequest();
        $context = $this->eventContextService->buildNullableContext($request);

        $event = new FormBuildEvent($builder, 'login', $context);
        $this->eventDispatcher->dispatch($event);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false, // CSRF token is handled by UserAuthenticator
        ]);
    }
}
