<?php

namespace App\Core\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;

class ExceptionLoggingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 255],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();
        $request = $this->requestStack->getCurrentRequest();

        $context = [
            'exception_class' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];

        if ($request) {
            $context['method'] = $request->getMethod();
            $context['uri'] = $request->getUri();
            $context['client_ip'] = $request->getClientIp();
            $context['headers'] = $request->headers->all();
            $context['query'] = $request->query->all();
        }

        // Log at critical so it always trips fingers_crossed and handlers
        $this->logger->critical('Unhandled exception caught by ExceptionLoggingSubscriber', $context);
    }
}
