<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormLayoutFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\FieldFactory\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FieldFactoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private FieldFactory $fieldFactory;

    protected function setUp(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager = $entityManager;

        $adminContextMock = $this->getMockBuilder(AdminContextInterface::class)->disableOriginalConstructor()->getMock();
        $adminContextMock->method('getCrud')->willReturn(new CrudDto());

        $requestStack = new RequestStack();
        $requestStack->push(new Request(attributes: [EA::CONTEXT_REQUEST_ATTRIBUTE => $adminContextMock]));

        $adminContextProvider = new AdminContextProvider($requestStack);

        $authorizationCheckerInterface = $this->getMockBuilder(AuthorizationCheckerInterface::class)->disableOriginalConstructor()->getMock();
        $authorizationCheckerInterface->method('isGranted')->willReturn(true);

        $this->fieldFactory = new FieldFactory(
            $adminContextProvider,
            $authorizationCheckerInterface,
            [],
            new FormLayoutFactory(),
        );
    }

    /**
     * @dataProvider stringNames
     */
    public function testStringNamesAreTurnedIntoFields(string $entity, string $property, string $expectedField): void
    {
        $fieldCollection = FieldCollection::new([$property]);

        $this->fieldFactory->processFields(new EntityDto($entity, $this->entityManager->getClassMetadata($entity)), $fieldCollection);

        $this->assertSame(
            $expectedField,
            array_values(array_map(fn (FieldDto $field) => $field->getFieldFqcn(), (array) $fieldCollection->getIterator()))[0],
            sprintf('Failed asserting that string "%s" is turned into a field of type "%s".', $property, $expectedField),
        );
    }

    public static function stringNames(): \Generator
    {
        yield [Project::class, 'rolesJson', Field\ArrayField::class];
        yield [Project::class, 'statesSimpleArray', Field\ArrayField::class];
        yield [Project::class, 'internal', Field\BooleanField::class];
        yield [Project::class, 'startDateMutable', Field\DateField::class];
        yield [Project::class, 'startDateImmutable', Field\DateField::class];
        yield [Project::class, 'startDateTimeMutable', Field\DateTimeField::class];
        yield [Project::class, 'startDateTimeImmutable', Field\DateTimeField::class];
        yield [Project::class, 'startDateTimeTzMutable', Field\DateTimeField::class];
        yield [Project::class, 'startDateTimeTzImmutable', Field\DateTimeField::class];
        yield [Project::class, 'id', Field\IdField::class];
        yield [Project::class, 'countInteger', Field\IntegerField::class];
        yield [Project::class, 'countSmallint', Field\IntegerField::class];
        yield [Project::class, 'priceDecimal', Field\NumberField::class];
        yield [Project::class, 'priceFloat', Field\NumberField::class];
        yield [Project::class, 'description', Field\TextareaField::class];
        yield [Project::class, 'name', Field\TextField::class];
        yield [Project::class, 'startTimeMutable', Field\TimeField::class];
        yield [Project::class, 'startTimeImmutable', Field\TimeField::class];
    }
}
