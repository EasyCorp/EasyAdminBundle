<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Security;

use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CustomVoterForNewCategory extends Voter
{
    public const NEW_CATEGORY = 'specific_new_category';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::NEW_CATEGORY === $attribute && $subject instanceof Category;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (null === $user) {
            return false;
        }

        return \in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
