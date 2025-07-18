<?php

namespace App\Interfaces\V1;

interface ApiRenderableExceptionV1
{
    public function getStatusCode(): int;
    public function getUserMessage(): string;
    public function getDeveloperHint(): ?string;
}