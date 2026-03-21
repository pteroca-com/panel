<?php

namespace App\Core\Handler;

interface CommandHandlerInterface
{
    public function handle(object $command): mixed;
}
