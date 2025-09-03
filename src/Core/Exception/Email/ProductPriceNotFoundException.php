<?php

namespace App\Core\Exception\Email;

use Exception;

class ProductPriceNotFoundException extends Exception
{
    public function __construct(string $message = 'Product price not found', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function forPriceAndProduct(int $priceId, int $productId): self
    {
        $message = sprintf('Price with ID %d not found for product ID %d', $priceId, $productId);
        return new self($message);
    }
}
