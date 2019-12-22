<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeProperty implements PropertyInterface
{
    use PropertyTrait;

    private $format;

    public function __construct()
    {
        $this->type = 'datetime';
        $this->formType = DateTimeType::class;
        $this->templateName = 'property/datetime';
    }

    public function setCustomOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('format')
            ->setAllowedTypes('format', 'string')
            ->setDefault('format', null);
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function build(PropertyDto $propertyDto, EntityDto $entityDto, ApplicationContext $applicationContext): PropertyDto
    {
        if (null === $propertyDto->getValue()) {
            $formattedValue = null;
        } else {
            $defaultFormat = $applicationContext->getCrud()->getDateTimeFormat();
            $propertyFormat = $this->format;
            $formattedValue = $propertyDto->getValue()->format($propertyFormat ?? $defaultFormat);
        }

        return $propertyDto->with([
            'customOptions' => [
                'format' => $this->format,
            ],
            'formattedValue' => $formattedValue,
        ]);
    }
}
