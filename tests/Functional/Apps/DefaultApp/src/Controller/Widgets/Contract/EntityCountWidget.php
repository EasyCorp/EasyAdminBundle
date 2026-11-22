<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\Contract;

use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetInterface;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetTrait;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * A widget built only with the public API, as a third-party package would do it.
 * It's a plain value object: the rows are counted by the EntityCount Twig
 * component that its template renders, because that component is a service.
 */
final class EntityCountWidget implements WidgetInterface
{
    use WidgetTrait;

    public const OPTION_ENTITY = 'entity';
    public const ASSET_PACKAGE = 'contract_widgets';

    public static function new(TranslatableInterface|string|null $label = null): self
    {
        return (new self())
            ->setLabel($label)
            ->setTemplatePath('admin/widgets/contract/entity_count.html.twig')
            ->setDefaultColumns(3)
            ->setCustomOption(self::OPTION_ENTITY, null)
            ->addCssFiles(Asset::new('entity-count.css')->package(self::ASSET_PACKAGE))
            ->addJsFiles(Asset::new('entity-count.js')->package(self::ASSET_PACKAGE));
    }

    /**
     * @param class-string $entityFqcn
     */
    public function setEntity(string $entityFqcn): self
    {
        return $this->setCustomOption(self::OPTION_ENTITY, $entityFqcn);
    }
}
