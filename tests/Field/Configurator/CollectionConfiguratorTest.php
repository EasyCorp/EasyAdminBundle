<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field\Configurator;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CollectionConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Field\AbstractFieldTest;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\ProjectDomain\ProjectCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\ProjectDomain\Project;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class CollectionConfiguratorTest extends AbstractFieldTest
{
    private EntityDto $projectDto;

    protected function setUp(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->projectDto = new EntityDto(Project::class, $entityManager->getClassMetadata(Project::class));

        /** @var CollectionConfigurator $collectionConfigurator */
        $collectionConfigurator = static::getContainer()->get(CollectionConfigurator::class);
        $this->configurator = $collectionConfigurator;
    }

    protected function getEntityDto(): EntityDto
    {
        return $this->projectDto;
    }

    /**
     * @dataProvider fields
     */
    public function test(FieldInterface $field): void
    {
        $field = $this->configure($field);
        $this->assertSame(CollectionType::class, $field->getFormType());
    }

    public static function fields(): \Generator
    {
        yield [CollectionField::new('projectIssues')];
        yield [CollectionField::new('favouriteProjectOf')];
        yield [CollectionField::new('projectTags')];
    }

    /**
     * @dataProvider failsOnOptionEntryUsesCrudFormIfPropertyIsNotAssociation
     */
    public function testFailsOnOptionEntryUsesCrudFormIfPropertyIsNotAssociation(FieldInterface $field): void
    {
        $field->setCustomOption(CollectionField::OPTION_ENTRY_USES_CRUD_FORM, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" collection field of "%s" cannot use the "useEntryCrudForm()" method because it is not a Doctrine association.',
            $field->getAsDto()->getProperty(),
            ProjectCrudController::class,
        ));

        $this->configure($field, controllerFqcn: ProjectCrudController::class);
    }

    public static function failsOnOptionEntryUsesCrudFormIfPropertyIsNotAssociation(): \Generator
    {
        yield [TextField::new('name')];
        yield [TextField::new('price')];
        yield [TextField::new('price.currency')];
    }

    /**
     * @dataProvider failsOnOptionEntryUsesCrudFormIfOptionEntryTypeIsUsed
     */
    public function testFailsOnOptionEntryUsesCrudFormIfOptionEntryTypeIsUsed(FieldInterface $field): void
    {
        $field->setCustomOption(CollectionField::OPTION_ENTRY_USES_CRUD_FORM, true)
            ->setCustomOption(CollectionField::OPTION_ENTRY_TYPE, 'foo')
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" collection field of "%s" can render its entries using a Symfony Form (via the "setEntryType()" method) or using an EasyAdmin CRUD Form (via the "useEntryCrudForm()" method) but you cannot use both methods at the same time. Remove one of those two methods.',
            $field->getAsDto()->getProperty(),
            ProjectCrudController::class,
        ));

        $this->configure($field, controllerFqcn: ProjectCrudController::class);
    }

    public static function failsOnOptionEntryUsesCrudFormIfOptionEntryTypeIsUsed(): \Generator
    {
        yield [CollectionField::new('projectIssues')];
        yield [CollectionField::new('favouriteProjectOf')];
        yield [CollectionField::new('projectTags')];
    }

    /**
     * @dataProvider failsOnOptionRenderAsEmbeddedCrudFormIfNoCrudControllerCanBeFound
     */
    public function testFailsOnOptionRenderAsEmbeddedCrudFormIfNoCrudControllerCanBeFound(FieldInterface $field): void
    {
        $field->getAsDto()->setDoctrineMetadata((array) $this->projectDto->getClassMetadata()->getAssociationMapping($field->getAsDto()->getProperty()));
        $field->setCustomOption(CollectionField::OPTION_ENTRY_USES_CRUD_FORM, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('The "%s" collection field of "%s" wants to render its entries using an EasyAdmin CRUD form. However, no CRUD form was found related to this field. You can either create a CRUD controller for the entity "%s" or pass the CRUD controller to use as the first argument of the "useEntryCrudForm()" method.',
            $field->getAsDto()->getProperty(),
            ProjectCrudController::class,
            $field->getAsDto()->getDoctrineMetadata()->get('targetEntity'),
        ));

        $this->configure($field, controllerFqcn: ProjectCrudController::class);
    }

    public static function failsOnOptionRenderAsEmbeddedCrudFormIfNoCrudControllerCanBeFound(): \Generator
    {
        yield [CollectionField::new('projectIssues')];
        yield [CollectionField::new('favouriteProjectOf')];
        yield [CollectionField::new('projectTags')];
    }
}
