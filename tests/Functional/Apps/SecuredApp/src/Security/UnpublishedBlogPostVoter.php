<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Security;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants access only to unpublished blog posts, to test permissions that need the entity to decide.
 *
 * @extends Voter<string, BlogPost>
 */
class UnpublishedBlogPostVoter extends Voter
{
    public const UNPUBLISHED_BLOG_POST = 'UNPUBLISHED_BLOG_POST';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::UNPUBLISHED_BLOG_POST === $attribute && $subject instanceof BlogPost;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return null === $subject->getPublishedAt();
    }
}
