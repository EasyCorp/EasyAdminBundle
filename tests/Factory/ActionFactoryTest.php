<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ActionFactory;
use PHPUnit\Framework\TestCase;

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
}
