<?php

namespace App\Core\Form;

use App\Core\Event\Form\FormBuildEvent;
use App\Core\Service\Event\EventContextService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResetPasswordRequestFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EventContextService $eventContextService,
        private readonly RequestStack $requestStack,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => $this->translator->trans('pteroca.recovery.email_address'),
                'attr' => ['autocomplete' => 'email'],
            ]);

        $request = $this->requestStack->getCurrentRequest();
        $context = $this->eventContextService->buildNullableContext($request);

        $formBuildEvent = new FormBuildEvent($builder, 'password_reset_request', $context);
        $this->eventDispatcher->dispatch($formBuildEvent);
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
