<?php

namespace App\Services;

class DocumentStockException extends \RuntimeException
{
    private $statusCode;

    public function __construct(string $message, int $statusCode = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
