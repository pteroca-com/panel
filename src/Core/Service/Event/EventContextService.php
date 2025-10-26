<?php

namespace App\Core\Service\Event;

use Symfony\Component\HttpFoundation\Request;

class EventContextService
{
    public function buildContext(Request $request, array $additionalContext = []): array
    {
        $context = [
            'ip' => $request->getClientIp(),
            'userAgent' => $request->headers->get('User-Agent'),
            'locale' => $request->getLocale(),
            'referer' => $request->headers->get('referer'),
        ];

        return array_merge($context, $additionalContext);
    }

    public function buildMinimalContext(Request $request, array $additionalContext = []): array
    {
        $context = [
            'ip' => $request->getClientIp(),
            'userAgent' => $request->headers->get('User-Agent'),
            'locale' => $request->getLocale(),
        ];

        return array_merge($context, $additionalContext);
    }

    public function buildNullableContext(?Request $request, array $additionalContext = []): array
    {
        if ($request === null) {
            return array_merge([
                'ip' => null,
                'userAgent' => null,
                'locale' => null,
                'referer' => null,
            ], $additionalContext);
        }

        return $this->buildContext($request, $additionalContext);
    }

    public function buildCliContext(string $commandName, array $additionalContext = []): array
    {
        $context = [
            'source' => 'cli',
            'command' => $commandName,
            'userAgent' => 'CLI',
            'locale' => null,
            'ip' => null,
        ];

        return array_merge($context, $additionalContext);
    }
}
