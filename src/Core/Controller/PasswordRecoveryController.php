<?php

namespace App\Core\Controller;

use App\Core\Enum\ViewNameEnum;
use App\Core\Event\Form\FormSubmitEvent;
use App\Core\Form\ResetPasswordFormType;
use App\Core\Form\ResetPasswordRequestFormType;
use App\Core\Service\Authorization\PasswordRecoveryService;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordRecoveryController extends AbstractController
{
    public function __construct(
        private readonly PasswordRecoveryService $passwordRecoveryService,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * @throws Exception
     */
    #[Route('/reset-password', name: 'app_forgot_password_request')]
    public function request(Request $request): Response
    {
        $errors = [];
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = [
                'email' => $form->get('email')->getData(),
            ];

            foreach ($form->all() as $fieldName => $field) {
                if ($field->getConfig()->getOption('mapped') === false) {
                    $formData[$fieldName] = $field->getData();
                }
            }

            $context = $this->buildMinimalEventContext($request);
            $submitEvent = $this->dispatchEvent(new FormSubmitEvent('password_reset_request', $formData, $context));

            if ($submitEvent->isPropagationStopped()) {
                $errors[] = $this->translator->trans('pteroca.recovery.plugin_validation_failed');
            } else {
                $this->passwordRecoveryService->createRecoveryRequest($submitEvent->getFormValue('email'));
                $this->addFlash('success', $this->translator->trans('pteroca.recovery.sent_if_exists'));
                return $this->redirectToRoute('app_login');
            }
        }

        if (empty($errors)) {
            $formErrors = $form->getErrors(true);
            $errors = array_map(fn ($error) => $error->getMessage(), iterator_to_array($formErrors));
        }

        $viewData = [
            'requestForm' => $form->createView(),
            'errors' => $errors,
        ];

        return $this->renderWithEvent(
            ViewNameEnum::PASSWORD_RESET_REQUEST,
            'panel/reset_password/request.html.twig',
            $viewData,
            $request
        );
    }

    /**
     * @throws Exception
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(Request $request, string $token): Response
    {
        $errors = [];
        if (!$this->passwordRecoveryService->validateRecoveryToken($token)) {
            $this->addFlash('danger', $this->translator->trans('pteroca.recovery.invalid_token'));
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = [
                'newPassword' => $form->get('newPassword')->getData(),
                'confirmPassword' => $form->get('confirmPassword')->getData(),
            ];

            foreach ($form->all() as $fieldName => $field) {
                if ($field->getConfig()->getOption('mapped') === false) {
                    $formData[$fieldName] = $field->getData();
                }
            }

            $context = $this->buildMinimalEventContext($request);
            $submitEvent = $this->dispatchEvent(new FormSubmitEvent('password_reset', $formData, $context));

            if ($submitEvent->isPropagationStopped()) {
                $errors[] = $this->translator->trans('pteroca.recovery.plugin_validation_failed');
            } else {
                if (!$this->passwordRecoveryService->updateUserPassword($token, $submitEvent->getFormValue('newPassword'))) {
                    $this->addFlash('danger', $this->translator->trans('pteroca.recovery.invalid_token'));
                } else {
                    $this->addFlash('success', $this->translator->trans('pteroca.recovery.success_password_changed'));
                    return $this->redirectToRoute('app_login');
                }
            }
        }

        if (empty($errors)) {
            $formErrors = $form->getErrors(true);
            $errors = array_map(fn ($error) => $error->getMessage(), iterator_to_array($formErrors));
        }

        $viewData = [
            'token' => $token,
            'resetForm' => $form->createView(),
            'errors' => $errors,
        ];

        return $this->renderWithEvent(
            ViewNameEnum::PASSWORD_RESET,
            'panel/reset_password/reset.html.twig',
            $viewData,
            $request
        );
    }
}
