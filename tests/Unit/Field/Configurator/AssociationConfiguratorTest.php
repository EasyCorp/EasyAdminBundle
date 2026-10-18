<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\AssociationConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\AbstractFieldTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AssociationConfiguratorTest extends AbstractFieldTestCase
{
    private EntityDto $rootEntityDto;
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->rootEntityDto = $this->createEntityDto(BlogPost::class);

        $adminUrlGenerator = $this->getMockBuilder(AdminUrlGeneratorInterface::class)->disableOriginalConstructor()->getMock();

        $this->requestStack = new RequestStack();

        $this->configurator = new AssociationConfigurator(
            static::getContainer()->get(EntityFactory::class),
            $adminUrlGenerator,
            $this->requestStack,
            static::getContainer()->get(ControllerFactory::class),
            static::getContainer()->get(FieldFactory::class),
            static::getContainer()->get(AuthorizationCheckerInterface::class),
            static::getContainer()->get(AdminContextFactory::class),
            static::getContainer()->get(EntityRepository::class),
        );
    }

    protected function getEntityDto(): EntityDto
    {
        return $this->rootEntityDto;
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
        $this->rootEntityDto = $this->createEntityDto(User::class);
        $field = AssociationField::new('favoriteCategory');

        $field->getAsDto()->setDoctrineMetadata((array) $this->rootEntityDto->getClassMetadata()->getAssociationMapping($field->getAsDto()->getProperty()));
        $field->setCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, CategoryCrudController::class);

        $fieldDto = $this->configure($field);
        $this->assertSame('toOne', $fieldDto->getCustomOption(AssociationField::OPTION_DOCTRINE_ASSOCIATION_TYPE));
        $this->assertSame(EntityType::class, $fieldDto->getFormType());
        $this->assertSame(Category::class, $fieldDto->getFormTypeOption('class'));
    }

    public function testToManyAssociation(): void
    {
        $field = AssociationField::new('categories');
        $field->getAsDto()->setDoctrineMetadata((array) $this->rootEntityDto->getClassMetadata()->getAssociationMapping($field->getAsDto()->getProperty()));
        $field->setCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, CategoryCrudController::class);

        $fieldDto = $this->configure($field, controllerFqcn: BlogPostCrudController::class);
        $this->assertSame('toMany', $fieldDto->getCustomOption(AssociationField::OPTION_DOCTRINE_ASSOCIATION_TYPE));
        $this->assertSame(EntityType::class, $fieldDto->getFormType());
        $this->assertSame(Category::class, $fieldDto->getFormTypeOption('class'));
    }

    public function testNestedAssociationWithCrudControllerSet(): void
    {
        $field = AssociationField::new('publisher.favoriteCategory')
            ->setCrudController(CategoryCrudController::class)
        ;

        $fieldDto = $this->configure($field);

        $this->assertSame(EntityType::class, $fieldDto->getFormType());
        $this->assertSame(Category::class, $fieldDto->getFormTypeOption('class'));
    }

    public function testNestedAssociationWithAutoConfiguration(): void
    {
        $field = AssociationField::new('publisher.favoriteCategory');

        $fieldDto = $this->configure($field);

        $this->assertSame(EntityType::class, $fieldDto->getFormType());
        $this->assertSame(Category::class, $fieldDto->getFormTypeOption('class'));
    }

    #[DataProvider('failsIfPropertyIsNotAssociation')]
    public function testFailsIfPropertyIsNotAssociation(string $rootEntityFqcn, FieldInterface $field): void
    {
        $this->rootEntityDto = $this->createEntityDto($rootEntityFqcn);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" field is not a Doctrine association, so it cannot be used as an association field.',
            $field->getAsDto()->getProperty(),
        ));

        $this->configure($field);
    }

    public static function failsIfPropertyIsNotAssociation(): \Generator
    {
        yield [BlogPost::class, TextField::new('title')];
        yield [User::class, TextField::new('address')];
        yield [User::class, TextField::new('address.city')]; // Doctrine embeddable
    }

    #[DataProvider('failsOnOptionRenderAsEmbeddedCrudFormIfPropertyIsCollection')]
    public function testFailsOnOptionRenderAsEmbeddedCrudFormIfPropertyIsCollection(string $rootEntityFqcn, FieldInterface $field): void
    {
        $this->rootEntityDto = $this->createEntityDto($rootEntityFqcn);
        $field->setCustomOption(AssociationField::OPTION_RENDER_AS_EMBEDDED_FORM, true)
            ->setCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, 'foo')
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" association field of "%s" is a to-many association but it\'s trying to use the "renderAsEmbeddedForm()" option, which is only available for to-one associations. If you want to use a CRUD form to render to-many associations, use a CollectionField instead of the AssociationField.',
            $field->getAsDto()->getProperty(),
            BlogPostCrudController::class,
        ));

        $this->configure($field, controllerFqcn: BlogPostCrudController::class);
    }

    public static function failsOnOptionRenderAsEmbeddedCrudFormIfPropertyIsCollection(): \Generator
    {
        yield [User::class, AssociationField::new('blogPosts')];
        yield [Category::class, AssociationField::new('blogPosts')];
        yield [BlogPost::class, AssociationField::new('categories')];
    }

    #[DataProvider('failsOnOptionRenderAsEmbeddedCrudFormIfNoCrudControllerCanBeFound')]
    public function testFailsOnOptionRenderAsEmbeddedCrudFormIfNoCrudControllerCanBeFound(FieldInterface $field): void
    {
        $field->getAsDto()->setDoctrineMetadata((array) $this->rootEntityDto->getClassMetadata()->getAssociationMapping($field->getAsDto()->getProperty()));
        $field->setCustomOption(AssociationField::OPTION_RENDER_AS_EMBEDDED_FORM, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s" association field of "%s" wants to render its contents using an EasyAdmin CRUD form. However, no CRUD form was found related to this field. You can either create a CRUD controller for the entity "%s" or pass the CRUD controller to use as the first argument of the "renderAsEmbeddedForm()" method.',
            $field->getAsDto()->getProperty(),
            BlogPostCrudController::class,
            $field->getAsDto()->getDoctrineMetadata()->get('targetEntity'),
        ));

        $this->configure($field, controllerFqcn: BlogPostCrudController::class);
    }

    public static function failsOnOptionRenderAsEmbeddedCrudFormIfNoCrudControllerCanBeFound(): \Generator
    {
        yield [AssociationField::new('author')];
    }

    public function testAssociationLinkIsRenderedWhenUserIsPermitted(): void
    {
        $targetCrud = new CrudDto();
        // no entity permission, no action permissions → both gates pass

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $this->configurator = $this->buildConfigurator(
            $authChecker,
            $this->buildUrlGeneratorReturning('http://expected-url'),
        );
        $this->primeTargetCrudDtoCache($this->configurator, CategoryCrudController::class, Action::INDEX, $targetCrud);

        $this->rootEntityDto = $this->createEntityDto(User::class);
        $fieldDto = $this->configure($this->buildFavoriteCategoryField());

        $this->assertSame('http://expected-url', $fieldDto->getCustomOption(AssociationField::OPTION_RELATED_URL));
    }

    public function testAssociationLinkIsHiddenWhenTargetEntityPermissionDenies(): void
    {
        $targetCrud = new CrudDto();
        $targetCrud->setEntityPermission('ROLE_DENIED');

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturnCallback(
            static fn ($attribute) => 'ROLE_DENIED' !== $attribute,
        );

        $this->configurator = $this->buildConfigurator(
            $authChecker,
            $this->buildUrlGeneratorReturning('http://should-not-appear'),
        );
        $this->primeTargetCrudDtoCache($this->configurator, CategoryCrudController::class, Action::INDEX, $targetCrud);

        $this->rootEntityDto = $this->createEntityDto(User::class);
        $fieldDto = $this->configure($this->buildFavoriteCategoryField());

        $this->assertNull($fieldDto->getCustomOption(AssociationField::OPTION_RELATED_URL));
    }

    public function testAssociationLinkIsHiddenWhenTargetActionPermissionDenies(): void
    {
        $targetCrud = new CrudDto();
        // no entity permission, so the gate that matters is EA_EXECUTE_ACTION

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturnCallback(
            static fn ($attribute) => Permission::EA_EXECUTE_ACTION !== $attribute,
        );

        $this->configurator = $this->buildConfigurator(
            $authChecker,
            $this->buildUrlGeneratorReturning('http://should-not-appear'),
        );
        $this->primeTargetCrudDtoCache($this->configurator, CategoryCrudController::class, Action::INDEX, $targetCrud);

        $this->rootEntityDto = $this->createEntityDto(User::class);
        $fieldDto = $this->configure($this->buildFavoriteCategoryField());

        $this->assertNull($fieldDto->getCustomOption(AssociationField::OPTION_RELATED_URL));
    }

    private function buildFavoriteCategoryField(): AssociationField
    {
        $field = AssociationField::new('favoriteCategory');
        $field->getAsDto()->setDoctrineMetadata(
            (array) $this->rootEntityDto->getClassMetadata()->getAssociationMapping('favoriteCategory'),
        );
        $field->setCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, CategoryCrudController::class);

        return $field;
    }

    private function buildConfigurator(
        AuthorizationCheckerInterface $authChecker,
        AdminUrlGeneratorInterface $urlGenerator,
    ): AssociationConfigurator {
        return new AssociationConfigurator(
            static::getContainer()->get(EntityFactory::class),
            $urlGenerator,
            $this->requestStack,
            static::getContainer()->get(ControllerFactory::class),
            static::getContainer()->get(FieldFactory::class),
            $authChecker,
            static::getContainer()->get(AdminContextFactory::class),
            static::getContainer()->get(EntityRepository::class),
        );
    }

    /**
     * Seeds the internal target-CrudDto cache so permission tests can run their gates against a
     * controlled CrudDto without exercising the full AdminContext-resolution chain.
     */
    private function primeTargetCrudDtoCache(AssociationConfigurator $configurator, string $crudControllerFqcn, string $crudAction, ?CrudDto $crudDto): void
    {
        $property = new \ReflectionProperty($configurator, 'targetCrudDtoCache');
        $cache = $property->getValue($configurator);
        $cache[$crudControllerFqcn.'::'.$crudAction] = $crudDto;
        $property->setValue($configurator, $cache);
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
