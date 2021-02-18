<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityUpdaterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\TraceableValidator;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityUpdater implements EntityUpdaterInterface
{
    private $propertyAccesor;
    private $validator;

    public function __construct(PropertyAccessorInterface $propertyAccesor, TraceableValidator $validator)
    {
        $this->propertyAccesor = $propertyAccesor;
        $this->validator = $validator;
    }

    public function updateProperty(EntityDto $entityDto, string $propertyName, $value): void
    {
        if (!$this->propertyAccesor->isWritable($entityDto->getInstance(), $propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" property of the "%s" entity is not writable.', $propertyName, $entityDto->getName()));
        }

        $entityInstance = $entityDto->getInstance();
        $this->propertyAccesor->setValue($entityInstance, $propertyName, $value);
        $entityDto->setInstance($entityInstance);

        $errors = $this->validator->validate($entityInstance);
        foreach ($errors as $violation) {
            throw new \RuntimeException($violation->getMessage());
        }
    }
}
