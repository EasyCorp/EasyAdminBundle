<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Size;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AvatarField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_IS_GRAVATAR_EMAIL = 'isGravatarEmail';
    public const OPTION_GRAVATAR_DEFAULT_IMAGE = 'gravatarDefaultImage';
    public const OPTION_HEIGHT = 'height';

    // see https://docs.gravatar.com/sdk/images/#default-image
    private const ALLOWED_GRAVATAR_DEFAULT_IMAGES = [
        'initials', 'color', '404', 'mp', 'identicon', 'monsterid', 'wavatar', 'retro', 'robohash', 'blank',
    ];

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/avatar')
            ->setFormType(TextType::class)
            ->addCssClass('field-avatar')
            ->setDefaultColumns('col-md-10 col-xxl-8')
            ->setSortable(false)
            ->setCustomOption(self::OPTION_IS_GRAVATAR_EMAIL, false)
            ->setCustomOption(self::OPTION_HEIGHT, null);
    }

    public function setHeight(int|string $heightInPixels): self
    {
        $semanticHeights = [Size::SM => 18, Size::MD => 24, Size::LG => 48, Size::XL => 96];

        if (!\is_int($heightInPixels) && !\array_key_exists($heightInPixels, $semanticHeights)) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s()" method must be either an integer (the height in pixels) or one of these string values: %s (%d given).', __METHOD__, implode(', ', $semanticHeights), $heightInPixels));
        }

        if (\is_string($heightInPixels)) {
            $heightInPixels = $semanticHeights[$heightInPixels];
        }

        if ($heightInPixels < 1) {
            throw new \InvalidArgumentException(sprintf('When passing an integer for the argument of the "%s()" method, the value must be 1 or higher (%d given).', __METHOD__, $heightInPixels));
        }

        $this->setCustomOption(self::OPTION_HEIGHT, $heightInPixels);

        return $this;
    }

    public function setIsGravatarEmail(bool $isGravatar = true): self
    {
        $this->setCustomOption(self::OPTION_IS_GRAVATAR_EMAIL, $isGravatar);

        return $this;
    }

    public function setGravatarDefaultImage(string $gravatarDefaultImage): self
    {
        $gravatarDefaultImage = trim($gravatarDefaultImage);

        if (
            !\in_array($gravatarDefaultImage, self::ALLOWED_GRAVATAR_DEFAULT_IMAGES, true)
            && !(str_starts_with($gravatarDefaultImage, 'http://') || str_starts_with($gravatarDefaultImage, 'https://'))
        ) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s()" method must be an image URL or one of these values: %s ("%s" given).', __METHOD__, implode(', ', self::ALLOWED_GRAVATAR_DEFAULT_IMAGES), $gravatarDefaultImage));
        }

        $this->setCustomOption(self::OPTION_GRAVATAR_DEFAULT_IMAGE, $gravatarDefaultImage);

        return $this;
    }
}
