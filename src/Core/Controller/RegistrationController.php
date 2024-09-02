<?php

namespace App\Core\Controller;

use App\Core\Entity\User;
use App\Core\Form\RegistrationFormType;
use App\Core\Security\UserAuthenticator;
use App\Core\Service\Authorization\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationService $registrationService,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        UserAuthenticator $authenticator,
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !$this->isTestMode()) {
            $user = $this->registrationService
                ->registerUser($user, $form->get('plainPassword')->getData());

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        $errors = $form->getErrors(true);
        $registrationErrors = [];
        foreach ($errors as $error) {
            $registrationErrors[] = $error->getMessage();
        }
        if ($this->isTestMode()) {
            $registrationErrors[] = $this->translator->trans('pteroca.demo.action_disabled');
        }
        $registrationErrors = implode('<br>', $registrationErrors);

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'errors' => $registrationErrors,
        ]);
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(
        string $token,
    ): Response {
        try {
            $this->registrationService->verifyEmail($token);
            $this->addFlash('success', $this->translator->trans('pteroca.register.verification_success'));
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('panel');
    }

    private function isTestMode(): bool
    {
        return isset($_ENV['DEMO_MODE']) && $_ENV['DEMO_MODE'] === 'true';
    }
}
