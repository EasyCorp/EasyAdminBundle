<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    private $timezoneId;
    private $timeFormatOrPattern;
    private $dateFormatOrPattern;

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

    public function setTimezone(string $timezoneId): self
    {
        $this->timezoneId = $timezoneId;

        return $this;
    }

    public function setFormat(string $timeFormatOrPattern, string $dateFormatOrPattern): self
    {
        $this->timeFormatOrPattern = $timeFormatOrPattern;
        $this->dateFormatOrPattern = $dateFormatOrPattern;

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
                'timezone' => $this->timezoneId,
                'time_format' => $this->timeFormatOrPattern,
                'date_format' => $this->dateFormatOrPattern,
            ],
            'formattedValue' => $formattedValue,
        ]);
    }
}
