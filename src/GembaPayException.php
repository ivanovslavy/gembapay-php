<?php

namespace GembaPay;

class GembaPayException extends \Exception
{
    private ?int $statusCode;

    public function __construct(string $message = '', ?int $statusCode = null, ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}
