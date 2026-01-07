<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Orm;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InvalidEntityException;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityUpdaterTest extends TestCase
{
    private PropertyAccessorInterface $propertyAccessor;
    private ValidatorInterface $validator;
    private EntityUpdater $entityUpdater;

    protected function setUp(): void
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->entityUpdater = new EntityUpdater($this->propertyAccessor, $this->validator);
    }

    public function testUpdatePropertySuccessfully(): void
    {
        $entity = new class {
            public string $name = 'original';
        };
        $entityDto = $this->createEntityDto($entity);

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityUpdater->updateProperty($entityDto, 'name', 'updated');

        $this->assertSame('updated', $entity->name);
        $this->assertSame($entity, $entityDto->getInstance());
    }

    public function testUpdatePropertyThrowsExceptionWhenPropertyIsNotWritable(): void
    {
        $entity = new class {
            private string $readOnlyProperty = 'value';
        };
        $entityDto = $this->createEntityDto($entity);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/The "readOnlyProperty" property of the ".*" entity is not writable/');

        $this->entityUpdater->updateProperty($entityDto, 'readOnlyProperty', 'newValue');
    }

    public function testUpdatePropertyThrowsExceptionWhenValidationFails(): void
    {
        $entity = new class {
            public string $email = 'valid@example.com';
        };
        $entityDto = $this->createEntityDto($entity);

        $violation = new ConstraintViolation(
            'This value is not a valid email address.',
            null,
            [],
            $entity,
            'email',
            'invalid-email'
        );
        $violations = new ConstraintViolationList([$violation]);

        $this->validator
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(InvalidEntityException::class);

        $this->entityUpdater->updateProperty($entityDto, 'email', 'invalid-email');
    }

    public function testUpdatePropertyWithNullValue(): void
    {
        $entity = new class {
            public ?string $description = 'some description';
        };
        $entityDto = $this->createEntityDto($entity);

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityUpdater->updateProperty($entityDto, 'description', null);

        $this->assertNull($entity->description);
        $this->assertSame($entity, $entityDto->getInstance());
    }

    public function testUpdatePropertyWithMultipleValidationViolations(): void
    {
        $entity = new class {
            public string $username = 'valid_user';
        };
        $entityDto = $this->createEntityDto($entity);

        $violation1 = new ConstraintViolation(
            'This value is too short.',
            null,
            [],
            $entity,
            'username',
            'x'
        );
        $violation2 = new ConstraintViolation(
            'This value should contain only letters and numbers.',
            null,
            [],
            $entity,
            'username',
            'x'
        );
        $violations = new ConstraintViolationList([$violation1, $violation2]);

        $this->validator
            ->method('validate')
            ->willReturn($violations);

        try {
            $this->entityUpdater->updateProperty($entityDto, 'username', 'x');
            $this->fail('Expected InvalidEntityException was not thrown');
        } catch (InvalidEntityException $e) {
            $this->assertCount(2, $e->violations);
        }
    }

    private function createEntityDto(object $instance): EntityDto
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getSingleIdentifierFieldName')->willReturn('id');

        return new EntityDto($instance::class, $metadata, null, $instance);
    }
}
