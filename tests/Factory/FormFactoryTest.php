<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class FormFactoryTest extends TestCase
{
    private FormFactoryInterface $symfonyFormFactory;
    private AdminUrlGeneratorInterface $adminUrlGenerator;
    private FormFactory $formFactory;

    protected function setUp(): void
    {
        $this->symfonyFormFactory = $this->createMock(FormFactoryInterface::class);
        $this->adminUrlGenerator = $this->createMock(AdminUrlGeneratorInterface::class);
        $this->formFactory = new FormFactory($this->symfonyFormFactory, $this->adminUrlGenerator);
    }

    public function testCreateEditFormBuilderReturnsFormBuilder(): void
    {
        $entityDto = $this->createEntityDto();
        $formOptions = KeyValueStore::new();
        $context = $this->createAdminContext('edit');

        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $capturedOptions = null;
        $this->symfonyFormFactory
            ->expects($this->once())
            ->method('createNamedBuilder')
            ->willReturnCallback(function ($name, $type, $data, $options) use (&$capturedOptions, $formBuilder) {
                $capturedOptions = $options;

                return $formBuilder;
            });

        $result = $this->formFactory->createEditFormBuilder($entityDto, $formOptions, $context);

        $this->assertSame($formBuilder, $result);
        $this->assertArrayHasKey('entityDto', $capturedOptions);
        $this->assertArrayHasKey('attr', $capturedOptions);
        $this->assertStringContainsString('ea-edit-form', $capturedOptions['attr']['class']);
        $this->assertSame('edit-Product-form', $capturedOptions['attr']['id']);
    }

    public function testCreateEditFormBuilderPreservesExistingCssClass(): void
    {
        $entityDto = $this->createEntityDto();
        $formOptions = KeyValueStore::new(['attr.class' => 'existing-class']);
        $context = $this->createAdminContext('edit');

        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $capturedOptions = null;
        $this->symfonyFormFactory
            ->expects($this->once())
            ->method('createNamedBuilder')
            ->willReturnCallback(function ($name, $type, $data, $options) use (&$capturedOptions, $formBuilder) {
                $capturedOptions = $options;

                return $formBuilder;
            });

        $this->formFactory->createEditFormBuilder($entityDto, $formOptions, $context);

        $this->assertStringContainsString('existing-class', $capturedOptions['attr']['class']);
        $this->assertStringContainsString('ea-edit-form', $capturedOptions['attr']['class']);
    }

    public function testCreateEditFormBuilderSetsTranslationDomain(): void
    {
        $entityDto = $this->createEntityDto();
        $formOptions = KeyValueStore::new();
        $context = $this->createAdminContext('edit', 'custom_domain');

        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $capturedOptions = null;
        $this->symfonyFormFactory
            ->expects($this->once())
            ->method('createNamedBuilder')
            ->willReturnCallback(function ($name, $type, $data, $options) use (&$capturedOptions, $formBuilder) {
                $capturedOptions = $options;

                return $formBuilder;
            });

        $this->formFactory->createEditFormBuilder($entityDto, $formOptions, $context);

        $this->assertSame('custom_domain', $capturedOptions['translation_domain']);
    }

    public function testCreateEditFormReturnsForm(): void
    {
        $entityDto = $this->createEntityDto();
        $formOptions = KeyValueStore::new();
        $context = $this->createAdminContext('edit');

        $form = $this->createMock(FormInterface::class);
        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formBuilder->method('getForm')->willReturn($form);

        $this->symfonyFormFactory
            ->method('createNamedBuilder')
            ->willReturn($formBuilder);

        $result = $this->formFactory->createEditForm($entityDto, $formOptions, $context);

        $this->assertSame($form, $result);
    }

    public function testCreateNewFormBuilderReturnsFormBuilder(): void
    {
        $entityDto = $this->createEntityDto();
        $formOptions = KeyValueStore::new();
        $context = $this->createAdminContext('new');

        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $capturedOptions = null;
        $this->symfonyFormFactory
            ->expects($this->once())
            ->method('createNamedBuilder')
            ->willReturnCallback(function ($name, $type, $data, $options) use (&$capturedOptions, $formBuilder) {
                $capturedOptions = $options;

                return $formBuilder;
            });

        $result = $this->formFactory->createNewFormBuilder($entityDto, $formOptions, $context);

        $this->assertSame($formBuilder, $result);
        $this->assertArrayHasKey('entityDto', $capturedOptions);
        $this->assertArrayHasKey('attr', $capturedOptions);
        $this->assertStringContainsString('ea-new-form', $capturedOptions['attr']['class']);
        $this->assertSame('new-Product-form', $capturedOptions['attr']['id']);
    }

    public function testCreateNewFormReturnsForm(): void
    {
        $entityDto = $this->createEntityDto();
        $formOptions = KeyValueStore::new();
        $context = $this->createAdminContext('new');

        $form = $this->createMock(FormInterface::class);
        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formBuilder->method('getForm')->willReturn($form);

        $this->symfonyFormFactory
            ->method('createNamedBuilder')
            ->willReturn($formBuilder);

        $result = $this->formFactory->createNewForm($entityDto, $formOptions, $context);

        $this->assertSame($form, $result);
    }

    public function testCreateFiltersFormReturnsHandledForm(): void
    {
        $filters = FilterCollection::new();
        $request = new Request(['filters' => ['name' => 'test']]);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request)->willReturnSelf();

        $this->adminUrlGenerator->method('setAll')->willReturnSelf();
        $this->adminUrlGenerator->method('setAction')->with(Action::INDEX)->willReturnSelf();
        $this->adminUrlGenerator->method('generateUrl')->willReturn('/admin?action=index');

        $capturedOptions = null;
        $this->symfonyFormFactory
            ->expects($this->once())
            ->method('createNamed')
            ->willReturnCallback(function ($name, $type, $data, $options) use (&$capturedOptions, $form) {
                $capturedOptions = $options;

                return $form;
            });

        $result = $this->formFactory->createFiltersForm($filters, $request);

        $this->assertSame($form, $result);
        $this->assertSame('GET', $capturedOptions['method']);
        $this->assertSame('/admin?action=index', $capturedOptions['action']);
        $this->assertInstanceOf(FilterCollection::class, $capturedOptions['ea_filters']);
    }

    public function testCreateFiltersFormPreservesQueryParameters(): void
    {
        $filters = FilterCollection::new();
        $request = new Request(['crudAction' => 'index', 'page' => 2]);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();

        $this->adminUrlGenerator
            ->expects($this->once())
            ->method('setAll')
            ->with(['crudAction' => 'index', 'page' => 2])
            ->willReturnSelf();
        $this->adminUrlGenerator->method('setAction')->willReturnSelf();
        $this->adminUrlGenerator->method('generateUrl')->willReturn('/admin');

        $this->symfonyFormFactory->method('createNamed')->willReturn($form);

        $this->formFactory->createFiltersForm($filters, $request);
    }

    private function createEntityDto(): EntityDto
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getSingleIdentifierFieldName')->willReturn('id');
        $metadata->method('hasAssociation')->willReturn(false);
        $metadata->fieldMappings = [];

        return new EntityDto('App\Entity\Product', $metadata);
    }

    private function createAdminContext(string $action, string $translationDomain = 'messages'): AdminContext
    {
        $crudDto = new CrudDto();
        $crudDto->setCurrentAction($action);

        return AdminContext::forTesting(
            RequestContext::forTesting(new Request()),
            CrudContext::forTesting($crudDto),
            null,
            I18nContext::forTesting('en', 'ltr', $translationDomain)
        );
    }
}
