<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Config;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use PHPUnit\Framework\TestCase;
use function Symfony\Component\Translation\t;

class ActionGroupTest extends TestCase
{
    public function testActionGroupWithAutomaticLabel()
    {
        $group = ActionGroup::new('group_name');
        $group->addAction(Action::new('action1')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertSame('group_name', $dto->getName());
        $this->assertSame('Group Name', $dto->getLabel());
    }

    public function testActionGroupWithCustomLabel()
    {
        $group = ActionGroup::new('group_name', 'Action Group 1');
        $group->addAction(Action::new('action1')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertSame('Action Group 1', $dto->getLabel());
    }

    public function testActionGroupWithTranslatableLabel()
    {
        $translatableLabel = t('Action Group 1');
        $group = ActionGroup::new('group_name', $translatableLabel);
        $group->addAction(Action::new('action1')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertEquals($translatableLabel, $dto->getLabel());
    }

    public function testActionGroupWithoutLabel()
    {
        $group = ActionGroup::new('group_name', false);
        $group->setIcon('fa fa-cog');
        $group->addAction(Action::new('action1')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertFalse($dto->getLabel());
    }

    public function testActionGroupWithIcon()
    {
        $group = ActionGroup::new('group_name', 'Action Group 1', 'fa fa-bars');
        $group->addAction(Action::new('action1')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertSame('fa fa-bars', $dto->getIcon());
    }

    public function testDropdownWithMainAction()
    {
        $mainAction = Action::new('main_action', 'Main Action')->linkToCrudAction('edit');
        $group = ActionGroup::new('group_name', 'Action Group 1');
        $group->addMainAction($mainAction);
        $group->addAction(Action::new('action1', 'Action 1')->linkToCrudAction(''));
        $group->addAction(Action::new('action2', 'Action 2')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertTrue($dto->hasMainAction());
        $this->assertSame('main_action', $dto->getMainAction()->getName());
        $this->assertCount(2, $dto->getActions());
    }

    public function testDropdownWithoutMainAction()
    {
        $group = ActionGroup::new('group_name', 'Action Group 1');
        $group->addAction(Action::new('action1', 'Action 1')->linkToCrudAction(''));
        $group->addAction(Action::new('action2', 'Action 2')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertFalse($dto->hasMainAction());
        $this->assertNull($dto->getMainAction());
        $this->assertCount(2, $dto->getActions());
    }

    public function testSetCssClass()
    {
        $group = ActionGroup::new('group_name', 'Action Group 1');
        $group->setCssClass('custom-dropdown');
        $group->addAction(Action::new('action1')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertSame('custom-dropdown', $dto->getCssClass());
        $this->assertSame('', $dto->getAddedCssClass());
    }

    public function testAddCssClass()
    {
        $group = ActionGroup::new('group_name', 'Action Group 1');
        $group->addCssClass('additional-class');
        $group->addAction(Action::new('action1')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertSame('', $dto->getCssClass());
        $this->assertSame('additional-class', $dto->getAddedCssClass());
    }

    public function testSetAndAddCssClass()
    {
        $group = ActionGroup::new('group_name', 'Action Group 1');
        $group->setCssClass('custom-dropdown')->addCssClass('additional-class');
        $group->addAction(Action::new('action1')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertSame('custom-dropdown', $dto->getCssClass());
        $this->assertSame('additional-class', $dto->getAddedCssClass());
    }

    public function testSetAndAddCssClassWithSpaces()
    {
        $group = ActionGroup::new('group_name', 'Action Group 1');
        $group->setCssClass('  custom-dropdown  class1  ')->addCssClass('  additional  class2  ');
        $group->addAction(Action::new('action1')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertSame('custom-dropdown  class1', $dto->getCssClass());
        $this->assertSame('additional  class2', $dto->getAddedCssClass());
    }

    public function testHtmlAttributes()
    {
        $group = ActionGroup::new('group_name', 'Action Group 1');
        $group->setHtmlAttributes([
            'data-test' => 'dropdown-5',
            'data-custom' => 'value',
        ]);
        $group->addAction(Action::new('action1')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertSame([
            'data-test' => 'dropdown-5',
            'data-custom' => 'value',
        ], $dto->getHtmlAttributes());
    }

    public function testAddAction()
    {
        $group = ActionGroup::new('group_name', 'Action Group 1');
        $group->addAction(Action::new('action1', 'Action 1')->linkToCrudAction(''));
        $group->addAction(Action::new('action2', 'Action 2')->linkToCrudAction(''));
        $group->addAction(Action::new('action3', 'Action 3')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $actions = $dto->getActions();
        $this->assertCount(3, $actions);
        $this->assertSame('action1', $actions[0]->getName());
        $this->assertSame('action2', $actions[1]->getName());
        $this->assertSame('action3', $actions[2]->getName());
    }

    public function testRemoveAction()
    {
        $group = ActionGroup::new('group_name', 'Action Group 1');
        $group->addAction(Action::new('action1', 'Action 1')->linkToCrudAction(''));
        $group->addAction(Action::new('action2', 'Action 2')->linkToCrudAction(''));
        $group->addAction(Action::new('action3', 'Action 3')->linkToCrudAction(''));
        $group->removeAction('action2');

        $dto = $group->getAsDto();
        $actions = $dto->getActions();
        $this->assertCount(2, $actions);
        $this->assertSame('action1', $actions[0]->getName());
        $this->assertSame('action3', $actions[1]->getName());
    }

    public function testAddDivider()
    {
        $group = ActionGroup::new('group_name', 'Action Group 3');
        $group->addAction(Action::new('action1', 'Action 1')->linkToCrudAction(''));
        $group->addDivider();
        $group->addAction(Action::new('action2', 'Action 2')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $items = $dto->getItems();
        $this->assertCount(3, $items);
        $this->assertInstanceOf(ActionDto::class, $items[0]);
        $this->assertSame(['type' => 'divider'], $items[1]);
        $this->assertInstanceOf(ActionDto::class, $items[2]);
    }

    public function testAddHeader()
    {
        $group = ActionGroup::new('group_name', 'Action Group 3');
        $group->addHeader('Group 1');
        $group->addAction(Action::new('action1', 'Action 1')->linkToCrudAction(''));
        $group->addHeader('Group 2');
        $group->addAction(Action::new('action2', 'Action 2')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $items = $dto->getItems();
        $this->assertCount(4, $items);
        $this->assertSame(['type' => 'header', 'content' => 'Group 1'], $items[0]);
        $this->assertInstanceOf(ActionDto::class, $items[1]);
        $this->assertSame(['type' => 'header', 'content' => 'Group 2'], $items[2]);
        $this->assertInstanceOf(ActionDto::class, $items[3]);
    }

    public function testAddHeaderWithTranslatable()
    {
        $group = ActionGroup::new('group_name', 'Action Group 3');
        $group->addHeader(t('Group 1'));
        $group->addAction(Action::new('action1', 'Action 1')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $items = $dto->getItems();
        $this->assertCount(2, $items);
        $this->assertSame('header', $items[0]['type']);
        $this->assertEquals(t('Group 1'), $items[0]['content']);
    }

    public function testMixedDropdownItems()
    {
        $group = ActionGroup::new('group_name', 'Action Group 3');
        $group->addHeader('Group 1');
        $group->addAction(Action::new('action1', 'Action 1')->linkToCrudAction(''));
        $group->addAction(Action::new('action2', 'Action 2')->linkToCrudAction(''));
        $group->addDivider();
        $group->addHeader('Group 2');
        $group->addAction(Action::new('action3', 'Action 3')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $items = $dto->getItems();
        $this->assertCount(6, $items);
        $this->assertSame(['type' => 'header', 'content' => 'Group 1'], $items[0]);
        $this->assertSame('action1', $items[1]->getName());
        $this->assertSame('action2', $items[2]->getName());
        $this->assertSame(['type' => 'divider'], $items[3]);
        $this->assertSame(['type' => 'header', 'content' => 'Group 2'], $items[4]);
        $this->assertSame('action3', $items[5]->getName());

        $actions = $dto->getActions();
        $this->assertCount(3, $actions);
    }

    public function testDisplayIf()
    {
        $entity = new class {
            public bool $isActive = true;
        };

        $group = ActionGroup::new('group_name', 'Action Group 6');
        $group->addAction(Action::new('action1')->linkToCrudAction(''));
        $group->displayIf(function ($entity) {
            return null !== $entity && $entity->isActive;
        });

        $dto = $group->getAsDto();

        $this->assertNotNull($dto);

        // ideally we would test $dto->isDisplayed($entity) but that requires passing
        // a EntityDto instance, that it's hard to build and can't be mocked because it's a final class
        $r = new \ReflectionClass($dto);
        $prop = $r->getProperty('displayCallable');
        $callable = $prop->getValue($dto);

        $this->assertTrue((bool) $callable($entity));
    }

    public function testSetTemplatePath()
    {
        $group = ActionGroup::new('group_name', 'Action Group 1');
        $group->setTemplatePath('custom/dropdown_template.html.twig');
        $group->addAction(Action::new('action1')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertSame('custom/dropdown_template.html.twig', $dto->getTemplatePath());
    }

    public function testDropdownWithoutActionsThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "group_name" action group must have at least one action');

        $group = ActionGroup::new('group_name', 'Action Group 1');
        $group->getAsDto();
    }

    public function testDropdownWithoutLabelAndIconThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The label and icon of an action group cannot be null at the same time');

        $group = ActionGroup::new('group_name', false);
        $group->addAction(Action::new('action1')->linkToCrudAction(''));
        $group->getAsDto();
    }

    public function testDropdownWithFalseLabelAndIcon()
    {
        $group = ActionGroup::new('group_name', false, 'fa fa-cog');
        $group->addAction(Action::new('action1')->linkToCrudAction(''));

        $dto = $group->getAsDto();
        $this->assertFalse($dto->getLabel());
        $this->assertSame('fa fa-cog', $dto->getIcon());
    }
}
