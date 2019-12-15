<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextProperty extends AbstractProperty
{
    private $maxLength = -1;

    public function __construct()
    {
        $this->type = 'text';
        $this->formType = TextType::class;
        $this->defaultTemplatePath = '@EasyAdmin/field_text.html.twig';
    }

    public function setCustomOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('maxLength')
            ->setAllowedTypes('maxLength', 'integer')
            ->setDefault('maxLength', -1);
    }

    public function setMaxLength(int $length): self
    {
        if ($length < 1) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s()" method must be 1 or higher (%d given).', __METHOD__, $length));
        }

        $this->maxLength = $length;

        return $this;
    }

    public function build(PropertyDto $propertyDto, EntityDto $entityDto, ApplicationContext $applicationContext): PropertyDto
    {
        if (-1 === $this->maxLength) {
            $pageName = $applicationContext->getPage()->getName();
            $this->maxLength = 'detail' === $pageName ? PHP_INT_MAX : 64;
        }

        $value = mb_substr($propertyDto->getValue(), 0, $this->maxLength);
        if ($this->maxLength < mb_strlen($propertyDto->getRawValue())) {
            $value .= '…';
        }

        return $propertyDto->with([
            'customOptions' => [
                'max_length' => $this->maxLength,
            ],
            'value' => $value,
        ]);
    }
}
