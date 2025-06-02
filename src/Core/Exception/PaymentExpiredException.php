<?php

namespace App\Core\Exception;

use Exception;

class PaymentExpiredException extends Exception
{
    public function __construct(string $message = 'Payment has expired', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
