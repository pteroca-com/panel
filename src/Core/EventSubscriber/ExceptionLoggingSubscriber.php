<?php

namespace App\Core\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionLoggingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.app')] private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        $request = $this->requestStack->getCurrentRequest();

        $context = [
            'exception_class' => $throwable::class,
            'message' => $throwable->getMessage(),
            'code' => $throwable->getCode(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => $throwable->getTraceAsString(),
        ];

        if ($request) {
            $context['request'] = [
                'method' => $request->getMethod(),
                'uri' => $request->getUri(),
                'route' => $request->attributes->get('_route'),
                'client_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
            ];
        }

        $this->logger->error('Unhandled exception caught by kernel subscriber', $context);
    }
}
