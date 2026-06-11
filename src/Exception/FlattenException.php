<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

use EasyCorp\Bundle\EasyAdminBundle\Context\ExceptionContext;
use Symfony\Component\ErrorHandler\Exception\FlattenException as BaseFlattenException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
final class FlattenException extends BaseFlattenException
{
    private ?ExceptionContext $context = null;

    /**
     * @param array<mixed> $headers
     */
    public static function create(\Exception $exception, ?int $statusCode = null, array $headers = []): static
    {
        return static::createFromThrowable($exception, $statusCode, $headers);
    }

    /**
     * @param array<mixed> $headers
     */
    public static function createFromThrowable(\Throwable $exception, ?int $statusCode = null, array $headers = []): static
    {
        if ($exception instanceof BaseException) {
            $e = parent::createFromThrowable($exception, $statusCode, $headers);
            $e->context = $exception->getContext();

            return $e;
        }

        $resolvedStatusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : ($statusCode ?? 500);

        $publicMessage = match (true) {
            $resolvedStatusCode >= 500 => 'exception.general_500',
            403 === $resolvedStatusCode => 'exception.general_403',
            404 === $resolvedStatusCode => 'exception.general_404',
            default => 'exception.general',
        };

        $e = parent::createFromThrowable($exception, $resolvedStatusCode, $headers);
        $e->context = new ExceptionContext(
            publicMessage: $publicMessage,
            debugMessage: $exception->getMessage(),
            statusCode: $resolvedStatusCode,
        );

        return $e;
    }

    public function getPublicMessage(): string
    {
        return $this->context->getPublicMessage();
    }

    public function getDebugMessage(): string
    {
        return $this->context->getDebugMessage();
    }

    /**
     * @return array<string>
     */
    public function getParameters(): array
    {
        return $this->context->getParameters();
    }

    /**
     * @return array<string>
     */
    public function getTranslationParameters(): array
    {
        return $this->context->getTranslationParameters();
    }

    public function getStatusCode(): int
    {
        return $this->context->getStatusCode();
    }
}
