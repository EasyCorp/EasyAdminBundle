<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Encapsulates HTTP request-related data for the admin context.
 * Don't use this class directly; use @AdminContext class instead.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class RequestContext
{
    public function __construct(
        private readonly Request $request,
        private readonly ?UserInterface $user,
    ) {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * Creates a RequestContext instance suitable for testing.
     */
    public static function forTesting(?Request $request = null, ?UserInterface $user = null): self
    {
        return new self(
            $request ?? new Request(),
            $user
        );
    }
}
