<?php

namespace App\Core\Exception\Email;

use Exception;

class ProductPriceNotFoundException extends Exception
{
    public function __construct(int $priceId, int $productId, int $code = 0, ?Exception $previous = null)
    {
        $message = sprintf('Price with ID %d not found for product ID %d', $priceId, $productId);
        parent::__construct($message, $code, $previous);
    }
}
