<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityUpdaterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityUpdater implements EntityUpdaterInterface
{
    private PropertyAccessorInterface $propertyAccessor;
    private ValidatorInterface $validator;

    public function __construct(PropertyAccessorInterface $propertyAccessor, ValidatorInterface $validator)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->validator = $validator;
    }

    public function updateProperty(EntityDto $entityDto, string $propertyName, $value): void
    {
        if (!$this->propertyAccessor->isWritable($entityDto->getInstance(), $propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" property of the "%s" entity is not writable.', $propertyName, $entityDto->getName()));
        }

        $entityInstance = $entityDto->getInstance();
        $this->propertyAccessor->setValue($entityInstance, $propertyName, $value);

        /** @var ConstraintViolationList $violations */
        $violations = $this->validator->validate($entityInstance);
        if (0 < \count($violations)) {
            throw new \RuntimeException((string) $violations);
        }

        $entityDto->setInstance($entityInstance);
    }
}
