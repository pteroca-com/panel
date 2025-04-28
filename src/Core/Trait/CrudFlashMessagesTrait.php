<?php

namespace App\Core\Trait;

trait CrudFlashMessagesTrait
{
    private function setFlashMessages(array $flashMessages): void
    {
        if (!empty($flashMessages)) {
            foreach ($flashMessages as $flashMessage) {
                $this->addFlash($flashMessage['type'] ?? 'danger', $flashMessage['message']);
            }
        }
    }
}
