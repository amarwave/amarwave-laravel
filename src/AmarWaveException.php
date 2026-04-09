<?php

declare(strict_types=1);

namespace AmarWave\Laravel;

use RuntimeException;

class AmarWaveException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        private readonly string $responseBody = '',
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
