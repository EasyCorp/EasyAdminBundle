<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TextProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public const OPTION_MAX_LENGTH = 'maxLength';

    public function __construct()
    {
        $this
            ->setType('text')
            ->setFormType(TextareaType::class)
            ->setTemplateName('property/text')
            ->setCustomOption(self::OPTION_MAX_LENGTH, null);
    }

    public function setMaxLength(int $length): self
    {
        if ($length < 1) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s()" method must be 1 or higher (%d given).', __METHOD__, $length));
        }

        $this->setCustomOption(self::OPTION_MAX_LENGTH, $length);

        return $this;
    }
}
