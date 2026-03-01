<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\UglyUrls;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests that legacy "ugly URLs" (with query parameters) are automatically
 * redirected to their corresponding "pretty URLs" when pretty URLs are enabled.
 *
 * This ensures backward compatibility for bookmarks and external links
 * that use the old URL format.
 */
class UglyToPrettyRedirectTest extends WebTestCase
{
    /**
     * @dataProvider provideUglyUrlRedirects
     */
    public function testUglyUrlsAreRedirectedToPrettyUrls(string $crudControllerFqcn, string $crudAction, ?int $entityId, array $extraQueryParameters, string $expectedPrettyUrlRedirect): void
    {
        $client = static::createClient();
        $client->followRedirects(false);

        $queryParameters = array_merge([
            EA::CRUD_CONTROLLER_FQCN => $crudControllerFqcn,
            EA::CRUD_ACTION => $crudAction,
            EA::ENTITY_ID => $entityId,
        ], $extraQueryParameters);

        $uglyUrl = sprintf('/admin?%s', http_build_query($queryParameters));
        $client->request('GET', $uglyUrl);

        $this->assertResponseRedirects($expectedPrettyUrlRedirect);
    }

    public static function provideUglyUrlRedirects(): iterable
    {
        // test with CRUD controller FQCN
        yield 'index with controller fqcn' => [CategoryCrudController::class, Action::INDEX, null, [], '/admin/category'];
        yield 'detail with controller fqcn' => [CategoryCrudController::class, Action::DETAIL, 1, [], '/admin/category/1'];
        yield 'new with controller fqcn' => [CategoryCrudController::class, Action::NEW, null, [], '/admin/category/new'];
        yield 'edit with controller fqcn' => [CategoryCrudController::class, Action::EDIT, 1, [], '/admin/category/1/edit'];

        // test with extra query parameters (should be preserved)
        yield 'index with extra params' => [CategoryCrudController::class, Action::INDEX, null, ['page' => 2, 'foo' => 'bar'], '/admin/category?page=2&foo=bar'];
    }
}
