<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

use EasyCorp\Bundle\EasyAdminBundle\Context\ExceptionContextInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class BaseException extends HttpException implements BaseExceptionInterface
{
    private ExceptionContextInterface $context;

    public function __construct(ExceptionContextInterface $context)
    {
        $this->context = $context;
        parent::__construct(
            $this->context->getStatusCode(),
            $this->context->getDebugMessage()
        );
    }

    public function getContext(): ExceptionContextInterface
    {
        return $this->context;
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

    public function getStatusCode(): int
    {
        return $this->context->getStatusCode();
    }
}
