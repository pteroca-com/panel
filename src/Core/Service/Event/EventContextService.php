<?php

namespace App\Core\Service\Event;

use Symfony\Component\HttpFoundation\Request;

/**
 * Serwis odpowiedzialny za budowanie contextu dla eventów w architekturze Event-Driven.
 *
 * Context zawiera informacje o środowisku wykonania eventu (IP, user agent, locale, etc.)
 * które mogą być wykorzystywane przez listenery do logowania, audytu czy analytics.
 */
class EventContextService
{
    /**
     * Buduje standardowy context z obiektu Request.
     *
     * @param Request $request Obiekt HTTP request
     * @param array $additionalContext Dodatkowe dane kontekstu specyficzne dla danego eventu
     * @return array Context zawierający: ip, userAgent, locale, referer oraz opcjonalne dodatkowe dane
     */
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

    /**
     * Buduje minimalny context bez pola referer.
     * Używany gdy referer nie jest istotny dla danego eventu.
     *
     * @param Request $request Obiekt HTTP request
     * @param array $additionalContext Dodatkowe dane kontekstu specyficzne dla danego eventu
     * @return array Context zawierający: ip, userAgent, locale oraz opcjonalne dodatkowe dane
     */
    public function buildMinimalContext(Request $request, array $additionalContext = []): array
    {
        $context = [
            'ip' => $request->getClientIp(),
            'userAgent' => $request->headers->get('User-Agent'),
            'locale' => $request->getLocale(),
        ];

        return array_merge($context, $additionalContext);
    }

    /**
     * Buduje context z opcjonalnego obiektu Request.
     * Używany w sytuacjach gdzie Request może nie być dostępny (np. komendy CLI).
     *
     * @param Request|null $request Opcjonalny obiekt HTTP request
     * @param array $additionalContext Dodatkowe dane kontekstu specyficzne dla danego eventu
     * @return array Context z wartościami null jeśli Request nie istnieje
     */
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
}
