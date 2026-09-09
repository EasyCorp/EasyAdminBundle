<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Form;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use EasyCorp\Bundle\EasyAdminBundle\Form\EventListener\CrudAutocompleteSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\ChoiceList\IdReader;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Twig\Environment;

class CrudAutocompleteSubscriberTest extends TestCase
{
    private const UUID_STRING = '1ecc57ff-5604-6800-83f2-5765340cd236';
    private const ULID_STRING = '01KH71DKRTZA5EJNDT8PKHH66M';

    protected function setUp(): void
    {
        if (!class_exists(PostgreSQLPlatform::class) || !class_exists(MySQLPlatform::class)) {
            $this->markTestSkipped('Doctrine DBAL 3.x+ is required (PostgreSQLPlatform/MySQLPlatform classes).');
        }

        // the subscriber inspects the globally registered Doctrine types, so reset
        // 'uuid' and 'ulid' to the Symfony UID types to not depend on the test execution order
        self::registerDoctrineType('uuid', UuidType::class);
        self::registerDoctrineType('ulid', UlidType::class);
    }

    protected function tearDown(): void
    {
        // some tests override the 'uuid' and 'ulid' types in the global Doctrine type
        // registry, so restore the Symfony UID types to not leak the overridden types
        // into other test classes running in the same PHPUnit process
        self::registerDoctrineType('uuid', UuidType::class);
        self::registerDoctrineType('ulid', UlidType::class);
    }

    /** @dataProvider idFieldInDifferentPlatformsData */
    public function testIdFieldInDifferentPlatforms(
        string $expectedValue,
        string $fieldType,
        string $fieldValue,
        string $platformClass,
    ): void {
        // Old doctrine versions does not support static platform instantiations
        $platform = new $platformClass();
        $capturedData = null;

        $repo = $this->createMock(EntityRepository::class);
        $repo
            ->expects($this->once())
            ->method('findBy')
            ->willReturnCallback(static function (array $criteria) use (&$capturedData) {
                $capturedData = $criteria['id'][0];

                return [];
            });

        $subscriber = new CrudAutocompleteSubscriber($this->createMock(Environment::class));
        $subscriber->preSubmit(
            $this->createFormEvent($platform, $repo, new FieldMapping($fieldType, 'id', 'id'), $fieldValue)
        );

        $this->assertSame($expectedValue, $capturedData, sprintf('%s fails to convert the id', $platform::class));
    }

    public static function idFieldInDifferentPlatformsData(): array
    {
        return [
            'PostgreSQL with ULID' => [
                Ulid::fromBase32(self::ULID_STRING)->toRfc4122(),
                'ulid',
                self::ULID_STRING,
                'Doctrine\DBAL\Platforms\PostgreSQLPlatform',
            ],
            'MySQL with ULID as native string' => [
                self::ULID_STRING,
                'string',
                self::ULID_STRING,
                'Doctrine\DBAL\Platforms\MySQLPlatform',
            ],
            'PostgreSQL with ULID as native string' => [
                self::ULID_STRING,
                'string',
                self::ULID_STRING,
                'Doctrine\DBAL\Platforms\PostgreSQLPlatform',
            ],
            'MySQL with ULID' => [
                Ulid::fromBase32(self::ULID_STRING)->toRfc4122(),
                'ulid',
                self::ULID_STRING,
                'Doctrine\DBAL\Platforms\MySQLPlatform',
            ],
            'PostgreSQL with UUID' => [
                self::UUID_STRING,
                'uuid',
                self::UUID_STRING,
                'Doctrine\DBAL\Platforms\PostgreSQLPlatform',
            ],
            'MySQL with UUID' => [
                Uuid::fromString(self::UUID_STRING)->toBinary(),
                'uuid',
                self::UUID_STRING,
                'Doctrine\DBAL\Platforms\MySQLPlatform',
            ],
            'PostgreSQL with UUID as native string' => [
                self::UUID_STRING,
                'string',
                self::UUID_STRING,
                'Doctrine\DBAL\Platforms\PostgreSQLPlatform',
            ],
            'MySQL with UUID as native string' => [
                self::UUID_STRING,
                'string',
                self::UUID_STRING,
                'Doctrine\DBAL\Platforms\MySQLPlatform',
            ],
        ];
    }

    /** @dataProvider idFieldWithNonSymfonyUidTypeData */
    public function testIdFieldWithNonSymfonyUidType(
        string $fieldType,
        string $fieldValue,
        string $platformClass,
    ): void {
        // third-party types registered for the 'uuid'/'ulid' type names may use a different
        // storage format than the Symfony UID types (e.g. ramsey/uuid-doctrine stores UUIDs
        // as strings), so the subscriber must pass the submitted values through unchanged
        Type::overrideType($fieldType, GuidType::class);

        $platform = new $platformClass();
        $capturedData = null;

        $repo = $this->createMock(EntityRepository::class);
        $repo
            ->expects($this->once())
            ->method('findBy')
            ->willReturnCallback(static function (array $criteria) use (&$capturedData) {
                $capturedData = $criteria['id'][0];

                return [];
            });

        $subscriber = new CrudAutocompleteSubscriber($this->createMock(Environment::class));
        $subscriber->preSubmit(
            $this->createFormEvent($platform, $repo, new FieldMapping($fieldType, 'id', 'id'), $fieldValue)
        );

        $this->assertSame($fieldValue, $capturedData, sprintf('%s must not convert the id of a non-Symfony "%s" type', $platform::class, $fieldType));
    }

    public static function idFieldWithNonSymfonyUidTypeData(): array
    {
        return [
            'MySQL with UUID' => [
                'uuid',
                self::UUID_STRING,
                'Doctrine\DBAL\Platforms\MySQLPlatform',
            ],
            'PostgreSQL with UUID' => [
                'uuid',
                self::UUID_STRING,
                'Doctrine\DBAL\Platforms\PostgreSQLPlatform',
            ],
            'MySQL with ULID' => [
                'ulid',
                self::ULID_STRING,
                'Doctrine\DBAL\Platforms\MySQLPlatform',
            ],
            'PostgreSQL with ULID' => [
                'ulid',
                self::ULID_STRING,
                'Doctrine\DBAL\Platforms\PostgreSQLPlatform',
            ],
        ];
    }

    private static function registerDoctrineType(string $name, string $className): void
    {
        Type::hasType($name) ? Type::overrideType($name, $className) : Type::addType($name, $className);
    }

    private function createFormEvent(
        object $platform,
        EntityRepository $repository,
        FieldMapping $fieldMapping,
        string $idValue,
    ): FormEvent {
        $className = 'App\Entity\Dummy';

        $idReader = $this->createMock(IdReader::class);
        $idReader->method('isIntId')->willReturn(false);
        $idReader->method('getIdField')->willReturn('id');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->method('getFieldMapping')
            ->with('id')
            ->willReturn($fieldMapping);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->with($className)->willReturn($classMetadata);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getRepository')->with($className)->willReturn($repository);

        $options = [
            'id_reader' => $idReader,
            'em' => $em,
            'class' => $className,
            'compound' => false,
            'choices' => [],
        ];

        $autocompleteConfig = $this->createMock(FormConfigInterface::class);
        $autocompleteConfig->method('getOptions')->willReturn($options);

        $autocompleteForm = $this->createMock(FormInterface::class);
        $autocompleteForm->method('getConfig')->willReturn($autocompleteConfig);

        $form = $this->createMock(FormInterface::class);
        $form->method('get')->with('autocomplete')->willReturn($autocompleteForm);
        $form->method('add')->willReturnSelf();

        $event = $this->createMock(FormEvent::class);
        $event->method('getData')->willReturn(['autocomplete' => $idValue]);
        $event->method('getForm')->willReturn($form);

        return $event;
    }
}
