<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Displays a list of items (e.g. the latest orders), each of them with
 * an optional icon, description, value and link.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ListWidget implements WidgetInterface
{
    use WidgetTrait;

    public const OPTION_ITEMS = 'items';
    public const OPTION_EMPTY_MESSAGE = 'emptyMessage';

    public static function new(TranslatableInterface|string|null $label = null): self
    {
        $widget = (new self())
            ->setLabel($label)
            ->setTemplateName('widget/list')
            ->setDefaultColumns(6)
            ->setCustomOption(self::OPTION_ITEMS, [])
            ->setCustomOption(self::OPTION_EMPTY_MESSAGE, null);

        $widget->setDataEntry('items', static function (WidgetContext $context): array {
            $widgetName = $context->getWidget()->getName() ?? $context->getWidget()->getType();
            $items = $context->resolve($context->getWidget()->getCustomOption(self::OPTION_ITEMS));

            if (!is_iterable($items)) {
                throw new \InvalidArgumentException(sprintf('The items of the "%s" list widget must be an iterable of "%s" objects (%s given).', $widgetName, ListItem::class, get_debug_type($items)));
            }

            // keys are not preserved because generators that use 'yield from' repeat them
            $items = \is_array($items) ? array_values($items) : iterator_to_array($items, false);
            foreach ($items as $item) {
                if (!$item instanceof ListItem) {
                    throw new \InvalidArgumentException(sprintf('The items of the "%s" list widget must be "%s" objects (%s given).', $widgetName, ListItem::class, get_debug_type($item)));
                }
            }

            return $items;
        });

        return $widget;
    }

    /**
     * @param iterable<ListItem>|callable $items The items of the list or a closure that returns them
     */
    public function setItems(iterable|callable $items): self
    {
        // strings can be the name of any function (e.g. 'phpinfo'), so they are never called;
        // arrays are always treated as the list of items, even if they look like a callable
        if (\is_string($items)) {
            throw new \InvalidArgumentException(sprintf('The "%s()" method only accepts iterables, closures and invokable objects (string given).', __METHOD__));
        }

        $this->setCustomOption(self::OPTION_ITEMS, $items);

        return $this;
    }

    /**
     * @param TranslatableInterface|string $message The message displayed when there are no items
     */
    public function setEmptyMessage(TranslatableInterface|string $message): self
    {
        $this->setCustomOption(self::OPTION_EMPTY_MESSAGE, $message);

        return $this;
    }
}
