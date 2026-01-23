<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ActionFactory;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

class ActionFactoryTest extends TestCase
{
    public function testAutomaticOrdering(): void
    {
        $actions = Actions::new();

        // add actions in a mixed order
        $actions->add('index', Action::new('action_warning')->linkToCrudAction('index')->asWarningAction());
        $actions->add('index', Action::new('action_primary')->linkToCrudAction('index')->asPrimaryAction());
        $actions->add('index', Action::new('action_danger_text')->linkToCrudAction('index')->asDangerAction()->asTextLink());
        $actions->add('index', Action::new('action_success')->linkToCrudAction('index')->asSuccessAction());
        $actions->add('index', Action::new('action_text')->linkToCrudAction('index')->asTextLink());
        $actions->add('index', Action::new('action_danger')->linkToCrudAction('index')->asDangerAction());
        $actions->add('index', Action::new('action_default')->linkToCrudAction('index'));

        $dto = $actions->getAsDto('index');
        $allActions = $dto->getActions()->all();
        $actionNames = array_keys($allActions);

        $expectedOrderBeforeSorting = [
            'action_warning',
            'action_primary',
            'action_danger_text',
            'action_success',
            'action_text',
            'action_danger',
            'action_default',
        ];

        // since automatic ordering is enabled by default, this should be the order before ActionFactory processes them
        $this->assertSame($expectedOrderBeforeSorting, $actionNames);
    }

    public function testDisableAutomaticOrdering(): void
    {
        $actions = Actions::new();

        // add actions in a mixed order
        $actions->add('index', Action::new('action_warning')->linkToCrudAction('index')->asWarningAction());
        $actions->add('index', Action::new('action_primary')->linkToCrudAction('index')->asPrimaryAction());
        $actions->add('index', Action::new('action_danger_text')->linkToCrudAction('index')->asDangerAction()->asTextLink());
        $actions->add('index', Action::new('action_success')->linkToCrudAction('index')->asSuccessAction());

        $actions->disableAutomaticOrdering();
        $dto = $actions->getAsDto('index');

        $this->assertFalse($dto->getUseAutomaticOrdering());
    }

    public function testReorderDisablesAutomaticOrdering(): void
    {
        $actions = Actions::new();

        $actions->add('index', Action::new('action_1')->linkToCrudAction('index'));
        $actions->add('index', Action::new('action_2')->linkToCrudAction('index'));
        $actions->add('index', Action::new('action_3')->linkToCrudAction('index'));

        // use reorder which should automatically disable automatic ordering
        $actions->reorder('index', ['action_3', 'action_1', 'action_2']);

        $dto = $actions->getAsDto('index');

        $this->assertFalse($dto->getUseAutomaticOrdering());

        // verify the custom order is preserved
        $allActions = $dto->getActions()->all();
        $actionNames = array_keys($allActions);
        $expectedOrder = ['action_3', 'action_1', 'action_2'];
        $this->assertSame($expectedOrder, $actionNames);
    }

    public function testBatchActionConfirmationOverridesGlobalSetting(): void
    {
        $actionFactory = $this->createActionFactory(false);

        $actions = Actions::new();
        $actions->addBatchAction(
            Action::new('publish')
                ->linkToUrl('/admin/publish')
                ->setConfirmationMessage('Confirm publish')
        );

        $processedActions = $actionFactory->processGlobalActions($actions->getAsDto(Crud::PAGE_INDEX));
        $action = $processedActions->get('publish');

        $this->assertNotNull($action);
        $attributes = $action->getHtmlAttributes();
        $this->assertArrayNotHasKey('data-action-batch-no-confirm', $attributes);
        $this->assertSame('modal', $attributes['data-bs-toggle']);
        $this->assertSame('#modal-batch-action', $attributes['data-bs-target']);
        $message = $attributes['data-batch-action-confirm-message'] ?? null;
        $this->assertInstanceOf(TranslatableInterface::class, $message);
        $this->assertSame('Confirm publish', $message->getMessage());
    }

    public function testBatchActionConfirmationContentCanBeCustomized(): void
    {
        $actionFactory = $this->createActionFactory(true);

        $actions = Actions::new();
        $actions->addBatchAction(
            Action::new('archive')
                ->linkToUrl('/admin/archive')
                ->setConfirmationContent('Archive content')
        );

        $processedActions = $actionFactory->processGlobalActions($actions->getAsDto(Crud::PAGE_INDEX));
        $action = $processedActions->get('archive');

        $this->assertNotNull($action);
        $attributes = $action->getHtmlAttributes();
        $content = $attributes['data-batch-action-confirm-content'] ?? null;
        $this->assertInstanceOf(TranslatableInterface::class, $content);
        $this->assertSame('Archive content', $content->getMessage());
    }

    public function testBatchActionConfirmationContentCanBeCleared(): void
    {
        $actionFactory = $this->createActionFactory(true);

        $actions = Actions::new();
        $actions->addBatchAction(
            Action::new('archive')
                ->linkToUrl('/admin/archive')
                ->setConfirmationContent(false)
        );

        $processedActions = $actionFactory->processGlobalActions($actions->getAsDto(Crud::PAGE_INDEX));
        $action = $processedActions->get('archive');

        $this->assertNotNull($action);
        $attributes = $action->getHtmlAttributes();
        $this->assertSame('', $attributes['data-batch-action-confirm-content']);
    }

    public function testBatchActionConfirmationContentUsesCrudDefault(): void
    {
        $actionFactory = $this->createActionFactoryWithContent(true, 'Default content');

        $actions = Actions::new();
        $actions->addBatchAction(
            Action::new('archive')
                ->linkToUrl('/admin/archive')
        );

        $processedActions = $actionFactory->processGlobalActions($actions->getAsDto(Crud::PAGE_INDEX));
        $action = $processedActions->get('archive');

        $this->assertNotNull($action);
        $attributes = $action->getHtmlAttributes();
        $content = $attributes['data-batch-action-confirm-content'] ?? null;
        $this->assertInstanceOf(TranslatableInterface::class, $content);
        $this->assertSame('Default content', $content->getMessage());
    }

    public function testBatchActionConfirmationContentOverridesCrudDefault(): void
    {
        $actionFactory = $this->createActionFactoryWithContent(true, 'Default content');

        $actions = Actions::new();
        $actions->addBatchAction(
            Action::new('archive')
                ->linkToUrl('/admin/archive')
                ->setConfirmationContent('Custom content')
        );

        $processedActions = $actionFactory->processGlobalActions($actions->getAsDto(Crud::PAGE_INDEX));
        $action = $processedActions->get('archive');

        $this->assertNotNull($action);
        $attributes = $action->getHtmlAttributes();
        $content = $attributes['data-batch-action-confirm-content'] ?? null;
        $this->assertInstanceOf(TranslatableInterface::class, $content);
        $this->assertSame('Custom content', $content->getMessage());
    }

    public function testBatchActionConfirmationCanBeDisabledPerAction(): void
    {
        $actionFactory = $this->createActionFactory(true);

        $actions = Actions::new();
        $actions->addBatchAction(
            Action::new('archive')
                ->linkToUrl('/admin/archive')
                ->setConfirmationMessage(false)
        );

        $processedActions = $actionFactory->processGlobalActions($actions->getAsDto(Crud::PAGE_INDEX));
        $action = $processedActions->get('archive');

        $this->assertNotNull($action);
        $attributes = $action->getHtmlAttributes();
        $this->assertSame('true', $attributes['data-action-batch-no-confirm']);
        $this->assertArrayNotHasKey('data-bs-toggle', $attributes);
        $this->assertArrayNotHasKey('data-batch-action-confirm-message', $attributes);
    }

    public function testActionConfirmationAddsConfirmationAttribute(): void
    {
        $actionFactory = $this->createActionFactory(true);

        $actions = Actions::new();
        $actions->add(
            Crud::PAGE_INDEX,
            Action::new('publish')
                ->createAsGlobalAction()
                ->linkToUrl('/admin/publish')
                ->setConfirmationMessage('Confirm publish')
        );

        $processedActions = $actionFactory->processGlobalActions($actions->getAsDto(Crud::PAGE_INDEX));
        $action = $processedActions->get('publish');

        $this->assertNotNull($action);
        $attributes = $action->getHtmlAttributes();
        $message = $attributes['data-action-confirm'] ?? null;
        $this->assertInstanceOf(TranslatableInterface::class, $message);
        $this->assertSame('Confirm publish', $message->getMessage());
        $this->assertArrayNotHasKey('data-bs-toggle', $attributes);
    }

    public function testActionConfirmationContentAddsContentAttribute(): void
    {
        $actionFactory = $this->createActionFactory(true);

        $actions = Actions::new();
        $actions->add(
            Crud::PAGE_INDEX,
            Action::new('publish')
                ->createAsGlobalAction()
                ->linkToUrl('/admin/publish')
                ->setConfirmationMessage('Confirm publish')
                ->setConfirmationContent('Confirm content')
        );

        $processedActions = $actionFactory->processGlobalActions($actions->getAsDto(Crud::PAGE_INDEX));
        $action = $processedActions->get('publish');

        $this->assertNotNull($action);
        $attributes = $action->getHtmlAttributes();
        $content = $attributes['data-action-confirm-content'] ?? null;
        $this->assertInstanceOf(TranslatableInterface::class, $content);
        $this->assertSame('Confirm content', $content->getMessage());
    }

    private function createActionFactory(bool|string|TranslatableInterface $askConfirmationOnBatchActions): ActionFactory
    {
        return $this->createActionFactoryWithContent($askConfirmationOnBatchActions, null);
    }

    private function createActionFactoryWithContent(bool|string|TranslatableInterface $askConfirmationOnBatchActions, bool|string|TranslatableInterface|null $batchActionConfirmationContent): ActionFactory
    {
        $adminContextProvider = $this->createMock(AdminContextProviderInterface::class);
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $adminUrlGenerator = $this->createMock(AdminUrlGeneratorInterface::class);

        $authChecker->method('isGranted')->willReturn(true);
        $adminUrlGenerator->method('unsetAllExcept')->willReturnSelf();
        $adminUrlGenerator->method('setAll')->willReturnSelf();
        $adminUrlGenerator->method('generateUrl')->willReturn('/admin');
        $adminUrlGenerator->method('setController')->willReturnSelf();
        $adminUrlGenerator->method('setAction')->willReturnSelf();
        $adminUrlGenerator->method('setEntityId')->willReturnSelf();
        $adminUrlGenerator->method('setRoute')->willReturnSelf();

        $crudDto = new CrudDto();
        $crudDto->setPageName(Crud::PAGE_INDEX);
        $crudDto->setEntityFqcn('App\\Entity\\Dummy');
        $crudDto->setAskConfirmationOnBatchActions($askConfirmationOnBatchActions);
        $crudDto->setBatchActionConfirmationContent($batchActionConfirmationContent);
        $crudDto->setControllerFqcn('App\\Controller\\DummyCrudController');

        $adminContext = AdminContext::forTesting(
            RequestContext::forTesting(new Request()),
            CrudContext::forTesting($crudDto),
            null,
            I18nContext::forTesting('en', 'ltr')
        );

        $adminContextProvider->method('getContext')->willReturn($adminContext);

        return new ActionFactory($adminContextProvider, $authChecker, $adminUrlGenerator);
    }
}
