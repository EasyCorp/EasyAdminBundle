<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Grants access to admins only when the subject is the request, like the
 * 'allow_if' expressions of 'access_control' rules that use the request.
 */
class RequestSubjectVoter implements VoterInterface
{
    public const ADMIN_REQUEST = 'ADMIN_REQUEST';

    public function vote(TokenInterface $token, mixed $subject, array $attributes, mixed $vote = null): int
    {
        if (!\in_array(self::ADMIN_REQUEST, $attributes, true)) {
            return self::ACCESS_ABSTAIN;
        }

        $isAdmin = [] !== array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $token->getRoleNames());

        return $subject instanceof Request && $isAdmin ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
    }
}
