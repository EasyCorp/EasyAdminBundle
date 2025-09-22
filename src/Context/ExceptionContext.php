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
    /** @var array<string> */
    private array $parameters;
    private int $statusCode;

    /**
     * @param array<string> $parameters
     */
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

    /**
     * @return array<string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array<string>
     */
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
