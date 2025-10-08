<?php

namespace App\Core\Trait;

use App\Core\Service\Event\EventContextService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Trait ułatwiający budowanie contextu dla eventów w kontrolerach.
 *
 * Wykorzystuje EventContextService do standaryzacji tworzenia contextu
 * bez konieczności ręcznego wyciągania danych z Request w każdym miejscu.
 * Automatycznie wstrzykuje EventDispatcher dla uproszczenia dispatchowania eventów.
 */
trait EventContextTrait
{
    private ?EventContextService $eventContextService = null;
    private ?EventDispatcherInterface $eventDispatcher = null;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setEventContextService(EventContextService $eventContextService): void
    {
        $this->eventContextService = $eventContextService;
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Buduje standardowy context z obiektu Request.
     *
     * @param Request $request Obiekt HTTP request
     * @param array $additionalContext Dodatkowe dane kontekstu (opcjonalne)
     * @return array Context gotowy do użycia w eventach
     */
    protected function buildEventContext(Request $request, array $additionalContext = []): array
    {
        return $this->getEventContextService()->buildContext($request, $additionalContext);
    }

    /**
     * Buduje minimalny context bez pola referer.
     *
     * @param Request $request Obiekt HTTP request
     * @param array $additionalContext Dodatkowe dane kontekstu (opcjonalne)
     * @return array Context gotowy do użycia w eventach
     */
    protected function buildMinimalEventContext(Request $request, array $additionalContext = []): array
    {
        return $this->getEventContextService()->buildMinimalContext($request, $additionalContext);
    }

    /**
     * Buduje context z opcjonalnego obiektu Request.
     * Używany w sytuacjach gdzie Request może nie być dostępny.
     *
     * @param Request|null $request Opcjonalny obiekt HTTP request
     * @param array $additionalContext Dodatkowe dane kontekstu (opcjonalne)
     * @return array Context gotowy do użycia w eventach
     */
    protected function buildNullableEventContext(?Request $request, array $additionalContext = []): array
    {
        return $this->getEventContextService()->buildNullableContext($request, $additionalContext);
    }

    /**
     * Dispatchuje event w skrócony sposób.
     * EventDispatcher jest automatycznie wstrzykiwany przez @Required.
     *
     * @param object $event Event do dispatchowania
     * @return object Zdispatchowany event (przydatne gdy event jest modyfikowany przez listenery)
     */
    protected function dispatchEvent(object $event): object
    {
        return $this->getEventDispatcher()->dispatch($event);
    }

    private function getEventContextService(): EventContextService
    {
        if ($this->eventContextService === null) {
            throw new \LogicException(
                'EventContextService not injected. Make sure the controller is registered as a service with autowiring enabled.'
            );
        }

        return $this->eventContextService;
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            throw new \LogicException(
                'EventDispatcher not injected. Make sure the controller is registered as a service with autowiring enabled.'
            );
        }

        return $this->eventDispatcher;
    }
}
