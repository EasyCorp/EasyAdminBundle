<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AccessControlRuleController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * An access_control rule can match on the attributes that routing adds to the request
 * ('attributes' and 'route' options) instead of on the path. Reaching the route through
 * '?routeName=' must give the same decision as browsing its own URL, also when the given
 * name is an alias of the route.
 */
class RouteNameAccessControlAttributesTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    /**
     * @dataProvider provideAccessControlDecisions
     */
    public function testRouteNameGivesTheSameDecisionAsTheRouteUrl(string $routeName, string $username, int $expectedStatusCode): void
    {
        if (Kernel::VERSION_ID < 60100) {
            $this->markTestSkipped('The "attributes" and "route" options of access_control require Symfony 6.1 or higher.');
        }

        $client = static::createClient();
        $credentials = ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234'];

        $client->request('GET', static::getContainer()->get('router')->generate($routeName), [], [], $credentials);
        static::assertResponseStatusCodeSame($expectedStatusCode, 'Symfony decided the rule of the route URL differently than expected.');

        $client->request('GET', '/admin?routeName='.$routeName, [], [], $credentials);
        static::assertResponseStatusCodeSame($expectedStatusCode);
        if (Response::HTTP_OK === $expectedStatusCode) {
            static::assertSame(AccessControlRuleController::SECRET, $client->getResponse()->getContent());
        } else {
            static::assertStringNotContainsString(AccessControlRuleController::SECRET, (string) $client->getResponse()->getContent());
        }
    }

    public static function provideAccessControlDecisions(): iterable
    {
        yield 'rule on the controller attribute, user' => ['access_control_controller_attribute', 'user', Response::HTTP_FORBIDDEN];
        yield 'rule on the controller attribute, admin' => ['access_control_controller_attribute', 'admin', Response::HTTP_OK];
        yield 'rule on the route name, user' => ['access_control_route_option', 'user', Response::HTTP_FORBIDDEN];
        yield 'rule on the route name, admin' => ['access_control_route_option', 'admin', Response::HTTP_OK];
        yield 'rule on the route name, alias, user' => ['route_option_shortcut', 'user', Response::HTTP_FORBIDDEN];
        yield 'rule on the route name, alias, admin' => ['route_option_shortcut', 'admin', Response::HTTP_OK];
    }
}
