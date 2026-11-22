<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\Contract;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetInterface;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetTrait;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * A widget built only with the public API whose data needs a service: the
 * rows are counted by RowCountWidgetConfigurator.
 */
final class RowCountWidget implements WidgetInterface
{
    use WidgetTrait;

    public const OPTION_ENTITY = 'entity';
    public const DATA_COUNT = 'count';

    public static function new(TranslatableInterface|string|null $label = null): self
    {
        return (new self())
            ->setLabel($label)
            ->setTemplatePath('admin/widgets/contract/row_count.html.twig')
            ->setDefaultColumns(3)
            ->setCustomOption(self::OPTION_ENTITY, null)
            ->setDataEntry(self::DATA_COUNT, null);
    }

    /**
     * @param class-string $entityFqcn
     */
    public function setEntity(string $entityFqcn): self
    {
        return $this->setCustomOption(self::OPTION_ENTITY, $entityFqcn);
    }
}
