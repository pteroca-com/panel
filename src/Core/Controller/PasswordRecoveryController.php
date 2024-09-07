<?php

namespace App\Core\Controller;

use App\Core\Form\ResetPasswordFormType;
use App\Core\Form\ResetPasswordRequestFormType;
use App\Core\Service\Authorization\PasswordRecoveryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('/reset-password', name: 'app_forgot_password_request')]
    public function request(Request $request): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            $this->passwordRecoveryService->createRecoveryRequest($email);
            $this->addFlash('success', $this->translator->trans('pteroca.recovery.sent_if_exists'));
            return $this->redirectToRoute('app_login');
        }

        return $this->render('panel/reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(Request $request, string $token): Response
    {
        if (!$this->passwordRecoveryService->validateRecoveryToken($token)) {
            $this->addFlash('danger', $this->translator->trans('pteroca.recovery.invalid_token'));
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            if (!$this->passwordRecoveryService->updateUserPassword($token, $newPassword)) {
                $this->addFlash('danger', $this->translator->trans('pteroca.recovery.invalid_token'));
            } else {
                $this->addFlash('success', $this->translator->trans('pteroca.recovery.success_password_changed'));
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('panel/reset_password/reset.html.twig', [
            'token' => $token,
            'resetForm' => $form->createView(),
        ]);
    }
}
