<?php

namespace App\Core\Trait;

use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

trait CrudFlashMessagesTrait
{
    private function setFlashMessages(array $flashMessages): void
    {
        if (empty($flashMessages)) {
            return;
        }

        $session = $this->requestStack->getSession();
        $usesDedupe = $session instanceof FlashBagAwareSessionInterface;

        foreach ($flashMessages as $flashMessage) {
            $type = $flashMessage['type'] ?? 'danger';
            $message = $flashMessage['message'];

            if ($usesDedupe) {
                $existingMessages = $session->getFlashBag()->peek($type);
                if (in_array($message, $existingMessages, true)) {
                    continue;
                }
            }

            $this->addFlash($type, $message);
        }
    }
}
