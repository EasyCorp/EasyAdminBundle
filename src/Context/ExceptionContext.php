<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ExceptionContext
{
    /**
     * @param array<string> $parameters
     */
    public function __construct(
        private readonly string $publicMessage,
        private readonly string $debugMessage = '',
        private readonly array $parameters = [],
        private readonly int $statusCode = 500,
    ) {
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
