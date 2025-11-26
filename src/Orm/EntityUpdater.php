<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityUpdaterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InvalidEntityException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityUpdater implements EntityUpdaterInterface
{
    public function __construct(private readonly PropertyAccessorInterface $propertyAccessor, private readonly ValidatorInterface $validator)
    {
    }

    public function updateProperty(EntityDto $entityDto, string $propertyName, mixed $value): void
    {
        if (!$this->propertyAccessor->isWritable($entityDto->getInstance(), $propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" property of the "%s" entity is not writable.', $propertyName, $entityDto->getName()));
        }

        $entityInstance = $entityDto->getInstance();
        $this->propertyAccessor->setValue($entityInstance, $propertyName, $value);

        /** @var ConstraintViolationList $violations */
        $violations = $this->validator->validate($entityInstance);
        if (0 < \count($violations)) {
            throw new InvalidEntityException($violations);
        }

        $entityDto->setInstance($entityInstance);
    }
}
