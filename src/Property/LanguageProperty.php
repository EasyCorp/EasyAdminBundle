<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;

class LanguageProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public const OPTION_SHOW_CODE = 'showCode';
    public const OPTION_SHOW_NAME = 'showName';

    public function __construct()
    {
        $this
            ->setType('language')
            ->setFormType(LanguageType::class)
            ->setTemplateName('property/language')
            ->setCustomOption(self::OPTION_SHOW_CODE, false)
            ->setCustomOption(self::OPTION_SHOW_NAME, true);
    }

    public function showCode(bool $isShown = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_CODE, $isShown);

        return $this;
    }

    public function showName(bool $isShown = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_NAME, $isShown);

        return $this;
    }
}
