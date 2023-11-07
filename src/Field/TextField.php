<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class TextField extends AbstractField
{
    public const OPTION_MAX_LENGTH = 'maxLength';
    public const OPTION_RENDER_AS_HTML = 'renderAsHtml';
    public const OPTION_STRIP_TAGS = 'stripTags';

    public static function new(string $propertyName, TranslatableInterface|string|false|null $label = null): FieldInterface
    {
        return parent::new($propertyName, $label)
            ->setTemplateName('crud/field/text')
            ->setFormType(TextType::class)
            ->addCssClass('field-text')
            ->setDefaultColumns('col-md-6 col-xxl-5')
            ->setCustomOption(self::OPTION_MAX_LENGTH, null)
            ->setCustomOption(self::OPTION_RENDER_AS_HTML, false)
            ->setCustomOption(self::OPTION_STRIP_TAGS, false);
    }

    /**
     * This option is ignored when using 'renderAsHtml()' to avoid
     * truncating contents in the middle of an HTML tag.
     */
    public function setMaxLength(int $length): self
    {
        if ($length < 1) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s()" method must be 1 or higher (%d given).', __METHOD__, $length));
        }

        $this->setCustomOption(self::OPTION_MAX_LENGTH, $length);

        return $this;
    }

    public function renderAsHtml(bool $asHtml = true): self
    {
        $this->setCustomOption(self::OPTION_RENDER_AS_HTML, $asHtml);

        return $this;
    }

    public function stripTags(bool $stripTags = true): self
    {
        $this->setCustomOption(self::OPTION_STRIP_TAGS, $stripTags);

        return $this;
    }
}
