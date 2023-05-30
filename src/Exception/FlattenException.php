<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

use EasyCorp\Bundle\EasyAdminBundle\Context\ExceptionContext;
use Symfony\Component\ErrorHandler\Exception\FlattenException as BaseFlattenException;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
final class FlattenException extends BaseFlattenException
{
    private ?ExceptionContext $context = null;

    public static function create(\Exception $exception, ?int $statusCode = null, array $headers = []): static
    {
        if (!$exception instanceof BaseException) {
            throw new \RuntimeException(sprintf('You should only try to create an instance of "%s" with a "EasyCorp\Bundle\EasyAdminBundle\Exception\BaseException" instance, or subclass. "%s" given.', __CLASS__, $exception::class));
        }

        $e = parent::create($exception, $statusCode, $headers);
        $e->context = $exception->getContext();

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

    public function getParameters(): array
    {
        return $this->context->getParameters();
    }

    public function getTranslationParameters(): array
    {
        return $this->context->getTranslationParameters();
    }

    public function getStatusCode(): int
    {
        return $this->context->getStatusCode();
    }
}
