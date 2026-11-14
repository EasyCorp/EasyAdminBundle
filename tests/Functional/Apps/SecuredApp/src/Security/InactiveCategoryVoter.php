<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Security;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Admins manage all categories; the other users only manage the existing
 * inactive ones, so they can't create categories.
 *
 * @extends Voter<string, Category|null>
 */
class InactiveCategoryVoter extends Voter
{
    public const MANAGE_INACTIVE_CATEGORY = 'MANAGE_INACTIVE_CATEGORY';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // the subject is null in the 'new' action because the category doesn't exist yet
        return self::MANAGE_INACTIVE_CATEGORY === $attribute && (null === $subject || $subject instanceof Category);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        return null !== $subject && !$subject->isActive();
    }
}
