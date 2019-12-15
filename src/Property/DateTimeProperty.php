<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeProperty extends AbstractProperty
{
    private $format;

    public function __construct()
    {
        $this->type = 'id';
        $this->formType = DateTimeType::class;
        $this->defaultTemplatePath = '@EasyAdmin/field_datetime.html.twig';
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
        $defaultFormat = $applicationContext->getCrud()->getDateTimeFormat();
        $propertyFormat = $this->format;
        $value = $propertyDto->getRawValue()->format($propertyFormat ?? $defaultFormat);

        return $propertyDto->with([
            'customOptions' => [
                'format' => $this->format,
            ],
            'value' => $value,
        ]);
    }
}
