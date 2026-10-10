<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Configurator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Security\CrudPermissionCheckerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\AssociationConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\DeveloperCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\ProjectCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\ProjectReleaseCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Developer;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\ProjectReleaseCategory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\ProjectTag;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\AbstractFieldTest;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RequestStack;

class AssociationConfiguratorTest extends AbstractFieldTest
{
    private EntityDto $projectDto;
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->projectDto = new EntityDto(Project::class, $entityManager->getClassMetadata(Project::class));

        $adminUrlGenerator = $this->getMockBuilder(AdminUrlGeneratorInterface::class)->disableOriginalConstructor()->getMock();

        $this->requestStack = new RequestStack();

        $this->configurator = new AssociationConfigurator(
            static::getContainer()->get(EntityFactory::class),
            $adminUrlGenerator,
            $this->requestStack,
            static::getContainer()->get(ControllerFactory::class),
            static::getContainer()->get(FieldFactory::class),
            static::getContainer()->get(CrudPermissionCheckerInterface::class),
            static::getContainer()->get(EntityRepository::class),
        );
    }

    protected function getEntityDto(): EntityDto
    {
        return $this->projectDto;
    }

    protected function getAdminContext(string $pageName, string $requestLocale, string $actionName, ?string $controllerFqcn = null): AdminContextInterface
    {
        $context = parent::getAdminContext($pageName, $requestLocale, $actionName, $controllerFqcn);
        $context->getRequest()->attributes->set(EA::CONTEXT_REQUEST_ATTRIBUTE, $context);
        $this->requestStack->push($context->getRequest());

        return $context;
    }

    public function testToOneAssociation(): void
    {
        $field = AssociationField::new('leadDeveloper');
        $entityDto = new EntityDto(Project::class, $this->createStub(ClassMetadata::class));
        $entityDto->setFields(new FieldCollection([$field]));

        $field->getAsDto()->setDoctrineMetadata((array) $this->projectDto->getClassMetadata()->getAssociationMapping($field->getAsDto()->getProperty()));
        $field->setCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, DeveloperCrudController::class);

        $fieldDto = $this->configure($field, controllerFqcn: ProjectCrudController::class);
        $this->assertSame('toOne', $fieldDto->getCustomOption(AssociationField::OPTION_DOCTRINE_ASSOCIATION_TYPE));
        $this->assertSame(EntityType::class, $fieldDto->getFormType());
        $this->assertSame(Developer::class, $fieldDto->getFormTypeOption('class'));
    }

    public function testToManyAssociation(): void
    {
        $field = AssociationField::new('projectTags');
        $field->getAsDto()->setDoctrineMetadata((array) $this->projectDto->getClassMetadata()->getAssociationMapping($field->getAsDto()->getProperty()));
        $field->setCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, DeveloperCrudController::class);

        $fieldDto = $this->configure($field, controllerFqcn: ProjectCrudController::class);
        $this->assertSame('toMany', $fieldDto->getCustomOption(AssociationField::OPTION_DOCTRINE_ASSOCIATION_TYPE));
        $this->assertSame(EntityType::class, $fieldDto->getFormType());
        $this->assertSame(ProjectTag::class, $fieldDto->getFormTypeOption('class'));
    }

    public function testNestedAssociationWithCrudControllerSet(): void
    {
        $field = AssociationField::new('latestRelease.category')
            ->setCrudController(ProjectReleaseCategoryCrudController::class)
        ;

        $fieldDto = $this->configure($field);

        $this->assertSame(EntityType::class, $fieldDto->getFormType());
        $this->assertSame(ProjectReleaseCategory::class, $fieldDto->getFormTypeOption('class'));
    }

    public function testNestedAssociationWithAutoConfiguration(): void
    {
        $field = AssociationField::new('latestRelease.category');

        $fieldDto = $this->configure($field);

        $this->assertSame(EntityType::class, $fieldDto->getFormType());
        $this->assertSame(ProjectReleaseCategory::class, $fieldDto->getFormTypeOption('class'));
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
        yield [TextField::new('price.currency')]; // Doctrine embeddable
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

    public function testAssociationLinkIsRenderedWhenPermissionCheckerGrants(): void
    {
        $this->configurator = $this->buildConfigurator(
            $this->buildPermissionChecker(true),
            $this->buildUrlGeneratorReturning('http://expected-url'),
        );

        $fieldDto = $this->configure($this->buildLeadDeveloperField(), controllerFqcn: ProjectCrudController::class);

        $this->assertSame('http://expected-url', $fieldDto->getCustomOption(AssociationField::OPTION_RELATED_URL));
    }

    public function testAssociationLinkIsHiddenWhenPermissionCheckerDenies(): void
    {
        $this->configurator = $this->buildConfigurator(
            $this->buildPermissionChecker(false),
            $this->buildUrlGeneratorReturning('http://should-not-appear'),
        );

        $fieldDto = $this->configure($this->buildLeadDeveloperField(), controllerFqcn: ProjectCrudController::class);

        $this->assertNull($fieldDto->getCustomOption(AssociationField::OPTION_RELATED_URL));
    }

    private function buildLeadDeveloperField(): AssociationField
    {
        $field = AssociationField::new('leadDeveloper');
        $field->getAsDto()->setDoctrineMetadata(
            (array) $this->projectDto->getClassMetadata()->getAssociationMapping('leadDeveloper'),
        );
        $field->setCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, DeveloperCrudController::class);

        return $field;
    }

    private function buildConfigurator(
        CrudPermissionCheckerInterface $permissionChecker,
        AdminUrlGeneratorInterface $urlGenerator,
    ): AssociationConfigurator {
        return new AssociationConfigurator(
            static::getContainer()->get(EntityFactory::class),
            $urlGenerator,
            $this->requestStack,
            static::getContainer()->get(ControllerFactory::class),
            static::getContainer()->get(FieldFactory::class),
            $permissionChecker,
            static::getContainer()->get(EntityRepository::class),
        );
    }

    /**
     * The permission rules themselves are covered by CrudPermissionCheckerTest; here only the
     * configurator's reaction to the verdict matters.
     */
    private function buildPermissionChecker(bool $isGranted): CrudPermissionCheckerInterface
    {
        $checker = $this->createStub(CrudPermissionCheckerInterface::class);
        $checker->method('isGranted')->willReturn($isGranted);

        return $checker;
    }

    private function buildUrlGeneratorReturning(string $url): AdminUrlGeneratorInterface
    {
        $generator = $this->getMockBuilder(AdminUrlGeneratorInterface::class)->disableOriginalConstructor()->getMock();
        $generator->method('setController')->willReturnSelf();
        $generator->method('setAction')->willReturnSelf();
        $generator->method('setEntityId')->willReturnSelf();
        $generator->method('unset')->willReturnSelf();
        $generator->method('generateUrl')->willReturn($url);

        return $generator;
    }
}
