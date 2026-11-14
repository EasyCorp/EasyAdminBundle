<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants access only to the 'admin' user, to test access_control rules that combine a role
 * with an attribute of a custom voter.
 *
 * @extends Voter<string, mixed>
 */
class AdminAreaVoter extends Voter
{
    public const ADMIN_AREA = 'ADMIN_AREA';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ADMIN_AREA === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return 'admin' === $token->getUserIdentifier();
    }
}
