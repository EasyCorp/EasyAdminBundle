<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class StringProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public function __construct()
    {
        $this
            ->setType('string')
            ->setFormType(TextType::class)
            ->setTemplateName('property/string')
            ->setCustomOption(TextProperty::OPTION_MAX_LENGTH, null);
    }

    public function setMaxLength(int $length): self
    {
        if ($length < 1) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s()" method must be 1 or higher (%d given).', __METHOD__, $length));
        }

        $this->setCustomOption(TextProperty::OPTION_MAX_LENGTH, $length);

        return $this;
    }
}
