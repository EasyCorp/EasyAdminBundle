<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\ConsensusStrategyKernel;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AccessControlRuleController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\PriorityStrategyKernel;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\UnanimousStrategyKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reaching a route through '?routeName=' must give the same access_control decision as
 * browsing its own URL. Symfony decides all the attributes of a rule at once with the
 * configured strategy, so a rule that mixes attributes of different voters can require
 * all of them.
 */
class RouteNameAccessDecisionStrategyTest extends WebTestCase
{
    private static string $kernelClass = Kernel::class;

    protected static function getKernelClass(): string
    {
        return self::$kernelClass;
    }

    /**
     * @dataProvider provideAccessControlDecisions
     *
     * @param class-string<Kernel> $kernelClass
     */
    public function testRouteNameGivesTheSameDecisionAsTheRouteUrl(string $kernelClass, string $routeName, string $username, int $expectedStatusCode): void
    {
        self::$kernelClass = $kernelClass;
        $client = static::createClient();
        $credentials = ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234'];
        $routePath = static::getContainer()->get('router')->generate($routeName);

        $client->request('GET', $routePath, [], [], $credentials);
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
        $allowed = Response::HTTP_OK;
        $denied = Response::HTTP_FORBIDDEN;

        // [ROLE_ADMIN, IS_AUTHENTICATED_FULLY]
        yield 'affirmative, role and full login, user' => [Kernel::class, 'access_control_role_and_full_login', 'user', $allowed];
        yield 'unanimous, role and full login, user' => [UnanimousStrategyKernel::class, 'access_control_role_and_full_login', 'user', $denied];
        yield 'unanimous, role and full login, admin' => [UnanimousStrategyKernel::class, 'access_control_role_and_full_login', 'admin', $allowed];
        yield 'consensus, role and full login, user' => [ConsensusStrategyKernel::class, 'access_control_role_and_full_login', 'user', $denied];
        yield 'consensus, role and full login, admin' => [ConsensusStrategyKernel::class, 'access_control_role_and_full_login', 'admin', $allowed];
        yield 'priority, role and full login, user' => [PriorityStrategyKernel::class, 'access_control_role_and_full_login', 'user', $allowed];

        // [ROLE_USER, ADMIN_AREA] where ADMIN_AREA is granted by a custom voter only to 'admin'
        yield 'affirmative, role and custom voter, user' => [Kernel::class, 'access_control_role_and_custom_voter', 'user', $allowed];
        yield 'unanimous, role and custom voter, user' => [UnanimousStrategyKernel::class, 'access_control_role_and_custom_voter', 'user', $denied];
        yield 'unanimous, role and custom voter, admin' => [UnanimousStrategyKernel::class, 'access_control_role_and_custom_voter', 'admin', $allowed];
        yield 'consensus, role and custom voter, user' => [ConsensusStrategyKernel::class, 'access_control_role_and_custom_voter', 'user', $denied];
    }
}
