<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;


use EasyCorp\Bundle\EasyAdminBundle\Context\ExceptionContextInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface BaseExceptionInterface extends \Throwable
{
    public function getContext(): ExceptionContextInterface;

    /**
     * @return string The message that can safely be displayed to end-users because it doesn't contain sensitive data
     */
    public function getPublicMessage(): string;

    /**
     * @return string The full exception message that is logged and it can contain sensitive data
     */
    public function getDebugMessage(): string;

    public function getParameters(): array;

    public function getStatusCode(): int;
}
