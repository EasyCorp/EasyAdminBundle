<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Context;

use EasyCorp\Bundle\EasyAdminBundle\Context\ExceptionContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

class ExceptionContextTest extends TestCase
{
    public function testTranslationParametersWrapNamesInPercentSigns(): void
    {
        $context = new ExceptionContext('exception.forbidden_action', '', [
            'crud_controller' => 'App\Controller\Admin\ProductCrudController',
            'action' => 'edit',
            '%entity_id%' => '42',
        ]);

        $this->assertSame([
            '%crud_controller%' => 'App\Controller\Admin\ProductCrudController',
            '%action%' => 'edit',
            '%entity_id%' => '42',
        ], $context->getTranslationParameters());
    }

    public function testTranslationReplacesPlaceholdersButNotWordsEqualToParameterNames(): void
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['exception.forbidden_action' => 'The requested action "%action%" can\'t be performed on this item.'], 'en', 'EasyAdminBundle');

        $context = new ExceptionContext('exception.forbidden_action', '', ['action' => 'edit']);

        $this->assertSame(
            'The requested action "edit" can\'t be performed on this item.',
            $translator->trans($context->getPublicMessage(), $context->getTranslationParameters(), 'EasyAdminBundle')
        );
    }

    public function testTranslationParametersAreEmptyWithoutParameters(): void
    {
        $this->assertSame([], (new ExceptionContext('exception.general'))->getTranslationParameters());
    }
}
