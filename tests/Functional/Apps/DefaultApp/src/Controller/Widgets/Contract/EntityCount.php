<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\Contract;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('contract:EntityCount', template: 'admin/widgets/contract/components/entity_count.html.twig')]
final class EntityCount
{
    // the test app doesn't run in debug mode, so this tells which widgets counted their rows and how many times
    /** @var array<string, int> */
    public static array $countsPerWidget = [];

    public string $widgetName;
    /** @var class-string */
    public string $entity;
    public ?string $range = null;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    public function getCount(): int
    {
        self::$countsPerWidget[$this->widgetName] = (self::$countsPerWidget[$this->widgetName] ?? 0) + 1;

        return $this->doctrine->getRepository($this->entity)->count([]);
    }
}
