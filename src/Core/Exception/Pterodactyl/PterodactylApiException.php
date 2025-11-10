<?php

namespace App\Core\Exception\Pterodactyl;

use Exception;
use Throwable;

class PterodactylApiException extends Exception
{
    protected ?int $statusCode = null;
    protected ?string $endpoint = null;
    protected ?string $method = null;
    protected ?string $responseBody = null;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        ?int $statusCode = null,
        ?string $endpoint = null,
        ?string $method = null,
        ?string $responseBody = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
        $this->endpoint = $endpoint;
        $this->method = $method;
        $this->responseBody = $responseBody;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}
