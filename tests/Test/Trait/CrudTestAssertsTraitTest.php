<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Test\Trait;

use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestAsserts;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class CrudTestAssertsTraitTest extends TestCase
{
    protected function setUp(): void
    {
    }
}

class CrudTestAssertsTraitTestClass
{
    use CrudTestAsserts;

    protected KernelBrowser $client;

    public function __construct(KernelBrowser $client)
    {
        $this->client = $client;
    }
}
