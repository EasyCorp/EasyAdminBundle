<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TextAreaProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public const OPTION_MAX_LENGTH = 'maxLength';
    public const OPTION_NUM_OF_ROWS = 'numOfRows';
    public const OPTION_RENDER_AS_HTML = 'renderAsHtml';

    public function __construct()
    {
        $this
            ->setType('textarea')
            ->setFormType(TextareaType::class)
            ->setTemplateName('property/textarea')
            ->setCustomOption(self::OPTION_MAX_LENGTH, null)
            ->setCustomOption(self::OPTION_NUM_OF_ROWS, 5)
            ->setCustomOption(self::OPTION_RENDER_AS_HTML, false);
    }

    public function setMaxLength(int $length): self
    {
        if ($length < 1) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s()" method must be 1 or higher (%d given).', __METHOD__, $length));
        }

        $this->setCustomOption(self::OPTION_MAX_LENGTH, $length);

        return $this;
    }

    public function setNumOfRows(int $rows): self
    {
        if ($rows < 1) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s()" method must be 1 or higher (%d given).', __METHOD__, $rows));
        }

        $this->setCustomOption(self::OPTION_NUM_OF_ROWS, $rows);

        return $this;
    }

    public function renderAsHtml(bool $asHtml = true): self
    {
        $this->setCustomOption(self::OPTION_RENDER_AS_HTML, $asHtml);

        return $this;
    }
}
