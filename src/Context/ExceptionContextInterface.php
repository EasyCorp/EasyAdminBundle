<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Context;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface ExceptionContextInterface
{
    public function getPublicMessage(): string;

    public function getDebugMessage(): string;

    public function getParameters(): array;

    public function getTranslationParameters(): array;

    public function getStatusCode(): int;
}
