<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Pascal CESCON <pascal.cescon@gmail.com>
 */
final class DateIntervalField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_FORMAT = 'format';

    public static function new(string $propertyName, TranslatableInterface|string|bool|null $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/date_interval')
            ->setFormType(DateIntervalType::class)
            ->setFormTypeOptions([
                'widget' => 'single_text',
                'input' => 'dateinterval',
                'with_years' => true,
                'with_months' => true,
                'with_weeks' => false,
                'with_days' => true,
                'with_hours' => true,
                'with_minutes' => true,
                'with_seconds' => true,
            ])
            ->addCssClass('field-date-interval')
            ->setDefaultColumns('col-md-6 col-xxl-5')
            ->setCustomOption(self::OPTION_FORMAT, null);
    }

    /**
     * Set a custom format string passed to DateInterval::format().
     *
     * When set, this overrides the localized rendering and the value is
     * displayed using the raw DateInterval::format() output.
     */
    public function setFormat(?string $format): self
    {
        $this->setCustomOption(self::OPTION_FORMAT, $format);

        return $this;
    }
}
