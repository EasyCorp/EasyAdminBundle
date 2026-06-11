<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Dto;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use PHPUnit\Framework\TestCase;

class EntityDtoTest extends TestCase
{
    public function testNewWithInstanceAcceptsMatchingInstance(): void
    {
        $dto = $this->createEntityDto(\stdClass::class);

        $newDto = $dto->newWithInstance($instance = new \stdClass());

        self::assertNotSame($dto, $newDto);
        self::assertSame($instance, $newDto->getInstance());
        self::assertSame(\stdClass::class, $newDto->getFqcn());
    }

    public function testNewWithInstanceRejectsMismatchedInstanceOnEmptyDto(): void
    {
        // a DTO whose $instance is null must still reject a mismatched instance;
        // before the fix, the instanceof guard was gated on "null !== $this->instance"
        // and this call silently produced a DTO whose $fqcn did not match its $instance
        // (CWE-441 Confused Deputy, exploited by the batchDelete cross-entity bypass).
        $dto = $this->createEntityDto(\stdClass::class);

        $this->expectException(\InvalidArgumentException::class);

        $dto->newWithInstance(new \DateTime());
    }

    public function testNewWithInstanceRejectsMismatchedInstanceOnPopulatedDto(): void
    {
        $dto = $this->createEntityDto(\stdClass::class, new \stdClass());

        $this->expectException(\InvalidArgumentException::class);

        $dto->newWithInstance(new \DateTime());
    }

    public function testSetInstanceAcceptsMatchingInstance(): void
    {
        $dto = $this->createEntityDto(\stdClass::class);

        $dto->setInstance($instance = new \stdClass());

        self::assertSame($instance, $dto->getInstance());
    }

    public function testSetInstanceAcceptsNull(): void
    {
        $dto = $this->createEntityDto(\stdClass::class, new \stdClass());

        $dto->setInstance(null);

        self::assertNull($dto->getInstance());
    }

    public function testSetInstanceRejectsMismatchedInstanceOnEmptyDto(): void
    {
        // same defense as in newWithInstance: the instanceof guard must run even when
        // $this->instance is null, so that a fresh DTO cannot be populated with an
        // instance whose class does not match its $fqcn.
        $dto = $this->createEntityDto(\stdClass::class);

        $this->expectException(\InvalidArgumentException::class);

        $dto->setInstance(new \DateTime());
    }

    public function testSetInstanceRejectsMismatchedInstanceOnPopulatedDto(): void
    {
        $dto = $this->createEntityDto(\stdClass::class, new \stdClass());

        $this->expectException(\InvalidArgumentException::class);

        $dto->setInstance(new \DateTime());
    }

    private function createEntityDto(string $fqcn, ?object $instance = null): EntityDto
    {
        return new EntityDto($fqcn, $this->createStub(ClassMetadata::class), null, $instance);
    }
}
