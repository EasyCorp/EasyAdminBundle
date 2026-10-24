<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Security;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants access only to active categories, to test permissions that depend on the entity.
 *
 * @extends Voter<string, Category>
 */
class ActiveCategoryVoter extends Voter
{
    public const ACTIVE_CATEGORY = 'ACTIVE_CATEGORY';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ACTIVE_CATEGORY === $attribute && $subject instanceof Category;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return $subject->isActive();
    }
}
