<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Security;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class AuthorizationCheckerTest extends TestCase
{
    /**
     * @var AuthorizationCheckerInterface|MockObject
     */
    private $authorizationChecker;

    /**
     * @var AuthorizationChecker
     */
    private $decorator;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->decorator = new AuthorizationChecker($this->authorizationChecker);
    }

    public function testIsGrantedEmptyAttributes()
    {
        $this->assertTrue($this->decorator->isGranted([]));
    }

    public function testIsGrantedScalarAttribute()
    {
        $attributes = 'ROLE_ADMIN';
        $subject = new \stdClass();
        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with($attributes, $subject)
            ->willReturn(true);

        $result = $this->decorator->isGranted($attributes, $subject);

        $this->assertTrue($result);
    }

    public function testIsGrantedSecondAttributeGranted()
    {
        $attributes = ['ROLE_ADMIN', 'ROLE_MANAGER'];
        $subject = new \stdClass();
        $this->authorizationChecker
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(['ROLE_ADMIN', $subject], ['ROLE_MANAGER', $subject])
            ->willReturnOnConsecutiveCalls(false, true);

        $result = $this->decorator->isGranted($attributes, $subject);

        $this->assertTrue($result);
    }

    public function testIsGrantedNoAttributeGranted()
    {
        $attributes = ['ROLE_ADMIN', 'ROLE_MANAGER'];
        $subject = new \stdClass();
        $this->authorizationChecker
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(['ROLE_ADMIN', $subject], ['ROLE_MANAGER', $subject])
            ->willReturnOnConsecutiveCalls(false, false);

        $result = $this->decorator->isGranted($attributes, $subject);

        $this->assertFalse($result);
    }

    public function testIsGrantedWillCatchAuthenticationCredentialsNotFoundException()
    {
        $attributes = 'ROLE_ADMIN';
        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with($attributes, null)
            ->willThrowException(new AuthenticationCredentialsNotFoundException());

        $result = $this->decorator->isGranted($attributes);

        $this->assertTrue($result);
    }
}
