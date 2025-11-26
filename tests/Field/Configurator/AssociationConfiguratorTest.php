<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field\Configurator;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\AssociationConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Field\AbstractFieldTest;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\ProjectDomain\DeveloperCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\ProjectDomain\ProjectCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\ProjectDomain\Developer;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\ProjectDomain\Project;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\ProjectDomain\ProjectTag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RequestStack;

class AssociationConfiguratorTest extends AbstractFieldTest
{
    private EntityDto $projectDto;

    protected function setUp(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->projectDto = new EntityDto(Project::class, $entityManager->getClassMetadata(Project::class));

        $adminUrlGenerator = $this->getMockBuilder(AdminUrlGeneratorInterface::class)->disableOriginalConstructor()->getMock();

        $this->configurator = new AssociationConfigurator(
            static::getContainer()->get(EntityFactory::class),
            $adminUrlGenerator,
            static::getContainer()->get(RequestStack::class),
            static::getContainer()->get(ControllerFactory::class),
            static::getContainer()->get(FieldFactory::class),
        );
    }

    protected function getEntityDto(): EntityDto
    {
        return $this->projectDto;
    }

    /**
     * @dataProvider toOneAssociation
     */
    public function testToOneAssociation(FieldInterface $field): void
    {
        $field->getAsDto()->setDoctrineMetadata((array) $this->projectDto->getClassMetadata()->getAssociationMapping($field->getAsDto()->getProperty()));
        $field->setCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, DeveloperCrudController::class);

        $field = $this->configure($field, controllerFqcn: ProjectCrudController::class);
        $this->assertSame('toOne', $field->getCustomOption(AssociationField::OPTION_DOCTRINE_ASSOCIATION_TYPE));
        $this->assertSame(EntityType::class, $field->getFormType());
        $this->assertSame(Developer::class, $field->getFormTypeOption('class'));
    }

    public static function toOneAssociation(): \Generator
    {
        yield [AssociationField::new('leadDeveloper')];
    }

    /**
     * @dataProvider toManyAssociation
     */
    public function testToManyAssociation(FieldInterface $field): void
    {
        $field->getAsDto()->setDoctrineMetadata((array) $this->projectDto->getClassMetadata()->getAssociationMapping($field->getAsDto()->getProperty()));
        $field->setCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, DeveloperCrudController::class);

        $field = $this->configure($field, controllerFqcn: ProjectCrudController::class);
        $this->assertSame('toMany', $field->getCustomOption(AssociationField::OPTION_DOCTRINE_ASSOCIATION_TYPE));
        $this->assertSame(EntityType::class, $field->getFormType());
        $this->assertSame(ProjectTag::class, $field->getFormTypeOption('class'));
    }

    public static function toManyAssociation(): \Generator
    {
        yield [AssociationField::new('projectTags')];
    }

    /**
     * @dataProvider failsIfPropertyIsNotAssociation
     */
    public function testFailsIfPropertyIsNotAssociation(FieldInterface $field): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" field is not a Doctrine association, so it cannot be used as an association field.',
            $field->getAsDto()->getProperty(),
        ));

        $this->configure($field);
    }

    public static function failsIfPropertyIsNotAssociation(): \Generator
    {
        yield [TextField::new('name')];
        yield [TextField::new('price')];
        yield [TextField::new('price.currency')];
    }

    /**
     * @dataProvider failsOnOptionRenderAsEmbeddedCrudFormIfPropertyIsCollection
     */
    public function testFailsOnOptionRenderAsEmbeddedCrudFormIfPropertyIsCollection(FieldInterface $field): void
    {
        $field->setCustomOption(AssociationField::OPTION_RENDER_AS_EMBEDDED_FORM, true)
            ->setCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, 'foo')
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" association field of "%s" is a to-many association but it\'s trying to use the "renderAsEmbeddedForm()" option, which is only available for to-one associations. If you want to use a CRUD form to render to-many associations, use a CollectionField instead of the AssociationField.',
            $field->getAsDto()->getProperty(),
            ProjectCrudController::class,
        ));

        $this->configure($field, controllerFqcn: ProjectCrudController::class);
    }

    public static function failsOnOptionRenderAsEmbeddedCrudFormIfPropertyIsCollection(): \Generator
    {
        yield [AssociationField::new('projectIssues')];
        yield [AssociationField::new('favouriteProjectOf')];
        yield [AssociationField::new('projectTags')];
    }

    /**
     * @dataProvider failsOnOptionRenderAsEmbeddedCrudFormIfNoCrudControllerCanBeFound
     */
    public function testFailsOnOptionRenderAsEmbeddedCrudFormIfNoCrudControllerCanBeFound(FieldInterface $field): void
    {
        $field->getAsDto()->setDoctrineMetadata((array) $this->projectDto->getClassMetadata()->getAssociationMapping($field->getAsDto()->getProperty()));
        $field->setCustomOption(AssociationField::OPTION_RENDER_AS_EMBEDDED_FORM, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" association field of "%s" wants to render its contents using an EasyAdmin CRUD form. However, no CRUD form was found related to this field. You can either create a CRUD controller for the entity "%s" or pass the CRUD controller to use as the first argument of the "renderAsEmbeddedForm()" method.',
            $field->getAsDto()->getProperty(),
            ProjectCrudController::class,
            $field->getAsDto()->getDoctrineMetadata()->get('targetEntity'),
        ));

        $this->configure($field, controllerFqcn: ProjectCrudController::class);
    }

    public static function failsOnOptionRenderAsEmbeddedCrudFormIfNoCrudControllerCanBeFound(): \Generator
    {
        yield [AssociationField::new('latestRelease')];
    }
}
