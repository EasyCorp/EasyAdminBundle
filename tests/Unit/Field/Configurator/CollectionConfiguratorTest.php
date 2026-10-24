<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CollectionConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\AbstractFieldTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class CollectionConfiguratorTest extends AbstractFieldTestCase
{
    private EntityDto $rootEntityDto;

    protected function setUp(): void
    {
        $this->rootEntityDto = $this->createEntityDto(BlogPost::class);

        /** @var CollectionConfigurator $collectionConfigurator */
        $collectionConfigurator = static::getContainer()->get(CollectionConfigurator::class);
        $this->configurator = $collectionConfigurator;
    }

    protected function getEntityDto(): EntityDto
    {
        return $this->rootEntityDto;
    }

    #[DataProvider('fields')]
    public function test(string $rootEntityFqcn, FieldInterface $field): void
    {
        $this->rootEntityDto = $this->createEntityDto($rootEntityFqcn);
        $field = $this->configure($field);
        $this->assertSame(CollectionType::class, $field->getFormType());
    }

    public static function fields(): \Generator
    {
        yield [User::class, CollectionField::new('blogPosts')];
        yield [Category::class, CollectionField::new('blogPosts')];
        yield [BlogPost::class, CollectionField::new('categories')];
        yield [BlogPost::class, CollectionField::new('metaData')];
    }

    public function testNestedCollections(): void
    {
        $field = CollectionField::new('author.blogPosts');
        $field->setCustomOption(CollectionField::OPTION_ENTRY_USES_CRUD_FORM, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The "author.blogPosts" collection field of "EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\BlogPostCrudController" wants to render its entries using an EasyAdmin CRUD form. However, no CRUD form was found related to this field. You can either create a CRUD controller for the entity "EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost" or pass the CRUD controller to use as the first argument of the "useEntryCrudForm()" method.'
        );

        $this->configure($field, pageName: Crud::PAGE_EDIT, controllerFqcn: BlogPostCrudController::class);
    }

    #[DataProvider('failsOnOptionEntryUsesCrudFormIfPropertyIsNotAssociation')]
    public function testFailsOnOptionEntryUsesCrudFormIfPropertyIsNotAssociation(string $rootEntityFqcn, FieldInterface $field): void
    {
        $this->rootEntityDto = $this->createEntityDto($rootEntityFqcn);
        $field->setCustomOption(CollectionField::OPTION_ENTRY_USES_CRUD_FORM, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" collection field of "%s" cannot use the "useEntryCrudForm()" method because it is not a Doctrine association.',
            $field->getAsDto()->getProperty(),
            BlogPostCrudController::class,
        ));

        $this->configure($field, pageName: Crud::PAGE_EDIT, controllerFqcn: BlogPostCrudController::class);
    }

    public static function failsOnOptionEntryUsesCrudFormIfPropertyIsNotAssociation(): \Generator
    {
        yield [BlogPost::class, TextField::new('title')];
        yield [User::class, TextField::new('address')];
        yield [User::class, TextField::new('address.city')];
    }

    #[DataProvider('failsOnOptionEntryUsesCrudFormIfOptionEntryTypeIsUsed')]
    public function testFailsOnOptionEntryUsesCrudFormIfOptionEntryTypeIsUsed(string $rootEntityFqcn, FieldInterface $field): void
    {
        $this->rootEntityDto = $this->createEntityDto($rootEntityFqcn);
        $field->setCustomOption(CollectionField::OPTION_ENTRY_USES_CRUD_FORM, true)
            ->setCustomOption(CollectionField::OPTION_ENTRY_TYPE, 'foo')
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" collection field of "%s" can render its entries using a Symfony Form (via the "setEntryType()" method) or using an EasyAdmin CRUD Form (via the "useEntryCrudForm()" method) but you cannot use both methods at the same time. Remove one of those two methods.',
            $field->getAsDto()->getProperty(),
            BlogPostCrudController::class,
        ));

        $this->configure($field, pageName: Crud::PAGE_EDIT, controllerFqcn: BlogPostCrudController::class);
    }

    public static function failsOnOptionEntryUsesCrudFormIfOptionEntryTypeIsUsed(): \Generator
    {
        yield [User::class, CollectionField::new('blogPosts')];
        yield [Category::class, CollectionField::new('blogPosts')];
        yield [BlogPost::class, CollectionField::new('categories')];
    }

    #[DataProvider('failsOnOptionRenderAsEmbeddedCrudFormIfNoCrudControllerCanBeFound')]
    public function testFailsOnOptionRenderAsEmbeddedCrudFormIfNoCrudControllerCanBeFound(string $rootEntityFqcn, FieldInterface $field): void
    {
        $this->rootEntityDto = $this->createEntityDto($rootEntityFqcn);
        $field->getAsDto()->setDoctrineMetadata((array) $this->rootEntityDto->getClassMetadata()->getAssociationMapping($field->getAsDto()->getProperty()));
        $field->setCustomOption(CollectionField::OPTION_ENTRY_USES_CRUD_FORM, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('The "%s" collection field of "%s" wants to render its entries using an EasyAdmin CRUD form. However, no CRUD form was found related to this field. You can either create a CRUD controller for the entity "%s" or pass the CRUD controller to use as the first argument of the "useEntryCrudForm()" method.',
            $field->getAsDto()->getProperty(),
            BlogPostCrudController::class,
            $field->getAsDto()->getDoctrineMetadata()->get('targetEntity'),
        ));

        $this->configure($field, pageName: Crud::PAGE_EDIT, controllerFqcn: BlogPostCrudController::class);
    }

    public static function failsOnOptionRenderAsEmbeddedCrudFormIfNoCrudControllerCanBeFound(): \Generator
    {
        yield [User::class, CollectionField::new('blogPosts')];
        yield [Category::class, CollectionField::new('blogPosts')];
        yield [BlogPost::class, CollectionField::new('categories')];
    }

    /**
     * Regression test for #7613: when a user supplies a formatValue() callable, the
     * (string)-casting loop inside formatCollection() must be skipped; otherwise an
     * item whose __toString() returns invalid UTF-8 makes Symfony String's truncate()
     * throw and the whole page returns a 500, even though the user's callable would
     * have overwritten the formatted value anyway.
     */
    public function testFormatCollectionShortCircuitsWhenFormatValueCallableIsSet(): void
    {
        $field = CollectionField::new('categories');

        // force the (string)-casting code path by simulating an 'array' doctrine type
        // (otherwise formatCollection() short-circuits earlier on association fields).
        $field->getAsDto()->setDoctrineMetadata(['type' => 'array']);

        $invalidUtf8Item = new class implements \Stringable {
            public function __toString(): string
            {
                return "valid\xC3\x28more";
            }
        };
        $field->setValue([$invalidUtf8Item]);
        $field->formatValue(static fn ($coll): int => is_countable($coll) ? \count($coll) : 0);

        // the user-provided callable is applied later by CommonPostConfigurator (not
        // exercised here), so this assertion checks the configurator's own early return.
        $fieldDto = $this->configure($field);

        $this->assertSame(1, $fieldDto->getFormattedValue());
    }
}
