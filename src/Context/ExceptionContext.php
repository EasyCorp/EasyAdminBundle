<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class ExceptionContext
{
    /**
     * @param array<string> $parameters
     */
    public function __construct(
        private string $publicMessage,
        private string $debugMessage = '',
        private array $parameters = [],
        private int $statusCode = 500,
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
