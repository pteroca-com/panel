<?php

namespace App\Core\Controller;

use App\Core\Entity\User;
use App\Core\Form\RegistrationFormType;
use App\Core\Service\Authorization\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
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
        #[Autowire(service: 'App\Core\Security\UserAuthenticator')]
        AuthenticatorInterface $authenticator,
    ): Response {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('panel');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registerActionResult = $this->registrationService
                ->registerUser($user, $form->get('plainPassword')->getData());

            if (!$registerActionResult->success) {
                $registrationErrors[] = $registerActionResult->error;
            } else {
                return $userAuthenticator->authenticateUser(
                    $registerActionResult->user,
                    $authenticator,
                    $request
                );
            }
        }

        if (empty($registrationErrors)) {
            $errors = $form->getErrors(true);
            $registrationErrors = array_map(fn ($error) => $error->getMessage(), iterator_to_array($errors));
        }

        return $this->render('panel/registration/register.html.twig', [
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
}
