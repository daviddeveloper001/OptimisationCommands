<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;
use App\Interfaces\V1\ApiRenderableExceptionV1;

class CustomerException extends Exception implements ApiRenderableExceptionV1
{
    private ?string $developerHint;

    public function __construct(
        string $message = 'Customer error occurred',
        ?string $developerHint = null,
        int $code = Response::HTTP_BAD_REQUEST,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->developerHint = $developerHint;
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }

    public function getUserMessage(): string
    {
        return $this->getMessage();
    }

    public function getDeveloperHint(): ?string
    {
        return $this->developerHint;
    }
}