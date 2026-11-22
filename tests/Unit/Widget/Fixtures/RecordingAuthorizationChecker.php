<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures;

use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class RecordingAuthorizationChecker implements AuthorizationCheckerInterface
{
    /** @var list<array{attribute: mixed, subject: mixed}> */
    public array $calls = [];

    /**
     * @param list<string> $grantedAttributes
     */
    public function __construct(private readonly array $grantedAttributes = [])
    {
    }

    public function isGranted(mixed $attribute, mixed $subject = null, ?AccessDecision $accessDecision = null): bool
    {
        $this->calls[] = ['attribute' => $attribute, 'subject' => $subject];

        return \in_array($attribute, $this->grantedAttributes, true);
    }
}
