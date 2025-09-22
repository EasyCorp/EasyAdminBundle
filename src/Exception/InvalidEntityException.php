<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidEntityException extends \RuntimeException
{
    public function __construct(
        public readonly ConstraintViolationListInterface $violations,
    ) {
        parent::__construct((string) $this->violations);
    }
}
