<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Security;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants access to all categories (including new ones, whose subject is NULL) to admins
 * and only to the active categories to other users.
 *
 * @extends Voter<string, Category|null>
 */
class ActiveCategoryOrAdminVoter extends Voter
{
    public const ACTIVE_CATEGORY_OR_ADMIN = 'ACTIVE_CATEGORY_OR_ADMIN';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ACTIVE_CATEGORY_OR_ADMIN === $attribute && (null === $subject || $subject instanceof Category);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return \in_array('ROLE_ADMIN', $token->getRoleNames(), true) || true === $subject?->isActive();
    }
}
