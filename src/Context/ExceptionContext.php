<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ExceptionContext
{
    private string $publicMessage;
    private string $debugMessage;
    private array $parameters;
    private int $statusCode;

    public function __construct(string $publicMessage, string $debugMessage = '', array $parameters = [], int $statusCode = 500)
    {
        $this->publicMessage = $publicMessage;
        $this->debugMessage = $debugMessage;
        $this->parameters = $parameters;
        $this->statusCode = $statusCode;
    }

    public function getPublicMessage(): string
    {
        return $this->publicMessage;
    }

    public function getDebugMessage(): string
    {
        return $this->debugMessage;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getTranslationParameters(): array
    {
        return array_map(
            static fn ($parameter): string => u($parameter)->ensureStart('%')->ensureEnd('%')->toString(),
            $this->parameters
        );
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
