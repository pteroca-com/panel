<?php

namespace App\Core\EventListener;

use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

#[AsEventListener(event: 'kernel.exception')]
class ExceptionListener
{
    public function __construct(
        private readonly Environment $twig,
        private readonly SettingService $settingService,
        private readonly KernelInterface $kernel,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if ($this->kernel->getEnvironment() !== 'prod') {
            return;
        }

        $exception = $event->getThrowable();
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        
        $currentTheme = $this->settingService->getSetting(SettingEnum::CURRENT_THEME->value) ?? 'default';
        
        $template = $this->findTemplate($statusCode, $currentTheme);
        
        if (!$template) {
            return;
        }

        try {
            $content = $this->twig->render($template, [
                'status_code' => $statusCode,
                'status_text' => Response::$statusTexts[$statusCode] ?? 'Unknown error',
                'exception' => $exception,
            ]);

            $response = new Response($content, $statusCode);
            $event->setResponse($response);
        } catch (\Exception $e) {
            return;
        }
    }

    private function findTemplate(int $statusCode, string $currentTheme): ?string
    {
        if ($currentTheme === 'default') {
            $specificTemplate = sprintf('@default_theme/bundles/TwigBundle/Exception/error%d.html.twig', $statusCode);
            $generalTemplate = '@default_theme/bundles/TwigBundle/Exception/error.html.twig';
            
            if ($this->templateExists($specificTemplate)) {
                return $specificTemplate;
            }
            
            if ($this->templateExists($generalTemplate)) {
                return $generalTemplate;
            }
        } else {
            $specificTemplate = sprintf('themes/%s/bundles/TwigBundle/Exception/error%d.html.twig', $currentTheme, $statusCode);
            $generalTemplate = sprintf('themes/%s/bundles/TwigBundle/Exception/error.html.twig', $currentTheme);
            
            if ($this->templateExists($specificTemplate)) {
                return $specificTemplate;
            }
            
            if ($this->templateExists($generalTemplate)) {
                return $generalTemplate;
            }
            
            $defaultSpecificTemplate = sprintf('@default_theme/bundles/TwigBundle/Exception/error%d.html.twig', $statusCode);
            $defaultGeneralTemplate = '@default_theme/bundles/TwigBundle/Exception/error.html.twig';
            
            if ($this->templateExists($defaultSpecificTemplate)) {
                return $defaultSpecificTemplate;
            }
            
            if ($this->templateExists($defaultGeneralTemplate)) {
                return $defaultGeneralTemplate;
            }
        }
        
        return null;
    }

    private function templateExists(string $template): bool
    {
        try {
            $this->twig->load($template);
            return true;
        } catch (\Twig\Error\LoaderError $e) {
            return false;
        }
    }
}
