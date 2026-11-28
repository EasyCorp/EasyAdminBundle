<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Security;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Custom voter that allows to ban an entity and its property from being processed.
 *
 * This is only for testing purposes, to simulate the behavior of the FieldFactory::processFields() method when
 * EA_VIEW_FIELD will return FALSE, on first entity, which is used to build table theaders.
 */
class CustomVoter extends Voter
{
    private ?object $banEntity = null;
    private ?string $banEntityProperty = null;

    protected function supports(string $attribute, mixed $subject): bool
    {
        return Permission::exists($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        static $entities = [];

        if ($subject instanceof EntityDto && $subject->getInstance()) {
            $entities[] = $subject;
        }

        foreach ($entities as $entityDto) {
            if ($this->banEntity && $entityDto->getInstance() === $this->banEntity) {
                // imitate the behavior of FieldFactory::processFields()
                // which removes the field from the entity if it is not granted
                if ($subject instanceof FieldDto) {
                    if ($fieldDto = $entityDto->getFields()?->getByProperty($this->banEntityProperty)) {
                        $entityDto->getFields()->unset($fieldDto);
                    }
                }
            }
        }

        return true;
    }

    public function ban(object $entity, ?string $propertyName = null): void
    {
        $this->banEntity = $entity;
        $this->banEntityProperty = $propertyName;
    }

    public function removeBan(): void
    {
        $this->banEntity = null;
        $this->banEntityProperty = null;
    }
}
