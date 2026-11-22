<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Displays a bar chart of a series of values (e.g. the sales of each month).
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ChartWidget implements WidgetInterface
{
    use WidgetTrait;

    public const OPTION_DATA = 'data';

    public static function new(TranslatableInterface|string|null $label = null): self
    {
        $widget = (new self())
            ->setLabel($label)
            ->setTemplateName('widget/chart')
            ->setDefaultColumns(6)
            ->setCustomOption(self::OPTION_DATA, []);

        $widget->setDataEntry('bars', static function (WidgetContext $context): array {
            $data = $context->resolve($context->getWidget()->getCustomOption(self::OPTION_DATA));

            return self::validateData($data, $context->getWidget()->getName() ?? $context->getWidget()->getType());
        });

        return $widget;
    }

    /**
     * @param array<string, int|float>|callable $data The chart values indexed by their labels (e.g. ['January' => 120, 'February' => 95])
     *                                                or a closure that returns them
     */
    public function setData(array|callable $data): self
    {
        if (\is_array($data)) {
            self::validateData($data, null);
        } elseif (\is_string($data)) {
            // strings can be the name of any function (e.g. 'phpinfo'), so they are never called
            throw new \InvalidArgumentException(sprintf('The "%s()" method only accepts arrays, closures and invokable objects (string given).', __METHOD__));
        }

        $this->setCustomOption(self::OPTION_DATA, $data);

        return $this;
    }

    /**
     * @return array<int|string, int|float>
     */
    private static function validateData(mixed $data, ?string $widgetName): array
    {
        $widgetDescription = null === $widgetName ? 'chart widget' : sprintf('"%s" chart widget', $widgetName);

        if (!\is_array($data)) {
            throw new \InvalidArgumentException(sprintf('The data of the %s must be an array of numbers indexed by their labels (%s given).', $widgetDescription, get_debug_type($data)));
        }

        // keys are not checked because PHP turns numeric labels (e.g. '2024') into integers
        foreach ($data as $label => $value) {
            // database queries often return numbers as strings (e.g. the result of SUM())
            if (\is_string($value) && is_numeric($value)) {
                $value += 0;
                $data[$label] = $value;
            }

            if ((!\is_int($value) && !\is_float($value)) || (\is_float($value) && !is_finite($value))) {
                throw new \InvalidArgumentException(sprintf('The data of the %s must be an array of numbers indexed by their labels, but the value of "%s" is not a finite number (%s given).', $widgetDescription, $label, get_debug_type($value)));
            }
        }

        return $data;
    }
}
