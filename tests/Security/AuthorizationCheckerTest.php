<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Security;

use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class AuthorizationCheckerTest extends TestCase
{
    private AuthorizationCheckerInterface $innerChecker;
    private AuthorizationChecker $authorizationChecker;

    protected function setUp(): void
    {
        $this->innerChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->authorizationChecker = new AuthorizationChecker($this->innerChecker);
    }

    public function testIsGrantedReturnsTrueWhenAttributeIsNull(): void
    {
        // the inner checker should never be called when attribute is null
        $this->innerChecker
            ->expects($this->never())
            ->method('isGranted');

        $result = $this->authorizationChecker->isGranted(null);

        $this->assertTrue($result);
    }

    public function testIsGrantedReturnsTrueWhenAttributeIsEmptyString(): void
    {
        // the inner checker should never be called when attribute is empty string
        $this->innerChecker
            ->expects($this->never())
            ->method('isGranted');

        $result = $this->authorizationChecker->isGranted('');

        $this->assertTrue($result);
    }

    public function testIsGrantedReturnsTrueWhenInnerCheckerGrantsAccess(): void
    {
        $this->innerChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN', null)
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('ROLE_ADMIN');

        $this->assertTrue($result);
    }

    public function testIsGrantedReturnsFalseWhenInnerCheckerDeniesAccess(): void
    {
        $this->innerChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_SUPER_ADMIN', null)
            ->willReturn(false);

        $result = $this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN');

        $this->assertFalse($result);
    }

    public function testIsGrantedPassesSubjectToInnerChecker(): void
    {
        $subject = new \stdClass();

        $this->innerChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_EDITOR', $subject)
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('ROLE_EDITOR', $subject);

        $this->assertTrue($result);
    }

    public function testIsGrantedReturnsTrueWhenSecurityIsNotConfigured(): void
    {
        // when security is not configured, AuthenticationCredentialsNotFoundException is thrown
        $this->innerChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN', null)
            ->willThrowException(new AuthenticationCredentialsNotFoundException());

        $result = $this->authorizationChecker->isGranted('ROLE_ADMIN');

        $this->assertTrue($result);
    }

    /**
     * @dataProvider provideValidAttributes
     */
    public function testIsGrantedDelegatesToInnerCheckerForValidAttributes(mixed $attribute): void
    {
        $this->innerChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with($attribute, null)
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted($attribute);

        $this->assertTrue($result);
    }

    public static function provideValidAttributes(): iterable
    {
        yield 'role string' => ['ROLE_USER'];
        yield 'permission string' => ['view'];
        yield 'array of roles' => [['ROLE_ADMIN', 'ROLE_USER']];
        yield 'zero string' => ['0'];
    }

    /**
     * @dataProvider provideNullLikeAttributes
     */
    public function testIsGrantedReturnsTrueWithoutDelegatingForNullLikeAttributes(mixed $attribute): void
    {
        $this->innerChecker
            ->expects($this->never())
            ->method('isGranted');

        $result = $this->authorizationChecker->isGranted($attribute);

        $this->assertTrue($result);
    }

    public static function provideNullLikeAttributes(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
    }
}
