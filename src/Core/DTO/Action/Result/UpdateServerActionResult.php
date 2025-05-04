<?php

namespace App\Core\DTO\Action\Result;

use App\Core\Enum\CrudFlashMessageTypeEnum;

class UpdateServerActionResult
{
    public function __construct(
        public array $messages = [],
    ) {}

    public function addMessage(string $message, CrudFlashMessageTypeEnum $type): void
    {
        $this->messages[] = [
            'message' => $message,
            'type' => $type->value,
        ];
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
