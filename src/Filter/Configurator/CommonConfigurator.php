<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Translation\EntityTranslationIdGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Generator\LabelGenerator;
use Symfony\Contracts\Translation\TranslatableInterface;
use function Symfony\Component\Translation\t;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CommonConfigurator implements FilterConfiguratorInterface
{
    public function __construct(
        private EntityTranslationIdGeneratorInterface $entityTranslationIdGenerator,
    ) {
    }

    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool
    {
        return true;
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        if (null === $filterDto->getLabel()) {
            $fieldLabel = $fieldDto?->getLabel();
            $translationDomain = $context->getI18n()->getTranslationDomain();

            if ($fieldLabel instanceof TranslatableInterface) {
                $label = $fieldLabel;
            } elseif (null !== $fieldLabel) {
                $label = t($fieldLabel, [], $translationDomain);
            } elseif ($context->isUseEntityTranslations()) {
                $label = $this->entityTranslationIdGenerator->generateForProperty($entityDto->getFqcn(), $filterDto->getProperty());
            } else {
                $label = t(LabelGenerator::humanize($filterDto->getProperty()), [], $translationDomain);
            }

            $filterDto->setLabel($label);
        }
    }
}
