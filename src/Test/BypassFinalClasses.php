<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Test;

use DG\BypassFinals;
use PHPUnit\Runner\BeforeTestHook;

/**
 * This was copied from: "How to Mock Final Classes in PHPUnit"
 * https://tomasvotruba.com/blog/2019/03/28/how-to-mock-final-classes-in-phpunit/
 * (c) Tomáš Votruba (https://github.com/TomasVotruba/)
 */
final class BypassFinalClasses implements BeforeTestHook
{
    public function executeBeforeTest(string $test): void
    {
        BypassFinals::enable();
    }
}
