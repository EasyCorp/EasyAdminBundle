<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use function Symfony\Component\String\u;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ChoiceConfigurator implements FieldConfiguratorInterface
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return ChoiceField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $isExpanded = $field->getCustomOption(ChoiceField::OPTION_RENDER_EXPANDED);

        $choices = $this->getChoices($field->getCustomOption(ChoiceField::OPTION_CHOICES), $entityDto, $field);
        if (empty($choices)) {
            throw new \InvalidArgumentException(sprintf('The "%s" choice field must define its possible choices using the setChoices() method.', $field->getProperty()));
        }

        $field->setFormTypeOptionIfNotSet('choices', $choices);
        $field->setFormTypeOptionIfNotSet('multiple', $field->getCustomOption(ChoiceField::OPTION_ALLOW_MULTIPLE_CHOICES));
        $field->setFormTypeOptionIfNotSet('expanded', $isExpanded);

        if ($isExpanded && ChoiceField::WIDGET_AUTOCOMPLETE === $field->getCustomOption(ChoiceField::OPTION_WIDGET)) {
            throw new \InvalidArgumentException(sprintf('The "%s" choice field wants to be displayed as an autocomplete widget and as an expanded list of choices at the same time, which is not possible. Use the renderExpanded() and renderAsNativeWidget() methods to change one of those options.', $field->getProperty()));
        }

        if (null === $field->getCustomOption(ChoiceField::OPTION_WIDGET)) {
            $field->setCustomOption(ChoiceField::OPTION_WIDGET, $isExpanded ? ChoiceField::WIDGET_NATIVE : ChoiceField::WIDGET_AUTOCOMPLETE);
        }

        if (ChoiceField::WIDGET_AUTOCOMPLETE === $field->getCustomOption(ChoiceField::OPTION_WIDGET)) {
            $field->setFormTypeOption('attr.data-widget', 'select2');
        }

        $field->setFormTypeOptionIfNotSet('placeholder', '');

        // the value of this form option must be a string to properly propagate it as an HTML attribute value
        $field->setFormTypeOption('attr.data-ea-escape-markup', $field->getCustomOption(ChoiceField::OPTION_ESCAPE_HTML_CONTENTS) ? 'true' : 'false');

        $fieldValue = $field->getValue();
        $isIndexOrDetail = \in_array($context->getCrud()->getCurrentPage(), [Crud::PAGE_INDEX, Crud::PAGE_DETAIL], true);
        if (null === $fieldValue || !$isIndexOrDetail) {
            return;
        }

        $badgeSelector = $field->getCustomOption(ChoiceField::OPTION_RENDER_AS_BADGES);
        $isRenderedAsBadge = null !== $badgeSelector && false !== $badgeSelector;

        $translationParameters = $context->getI18n()->getTranslationParameters();
        $translationDomain = $context->getI18n()->getTranslationDomain();
        $selectedChoices = [];
        $flippedChoices = array_flip($choices);
        // $value is a scalar for single selections and an array for multiple selections
        foreach (array_values((array) $fieldValue) as $selectedValue) {
            if (null !== $selectedChoice = $flippedChoices[$selectedValue] ?? null) {
                $choiceValue = $this->translator->trans($selectedChoice, $translationParameters, $translationDomain);
                $selectedChoices[] = $isRenderedAsBadge
                    ? sprintf('<span class="%s">%s</span>', $this->getBadgeCssClass($badgeSelector, $selectedValue, $field), $choiceValue)
                    : $choiceValue;
            }
        }
        $field->setFormattedValue(implode($isRenderedAsBadge ? '' : ', ', $selectedChoices));
    }

    private function getChoices($choiceGenerator, EntityDto $entity, FieldDto $field): array
    {
        if (null === $choiceGenerator) {
            return [];
        }

        if (\is_array($choiceGenerator)) {
            return $choiceGenerator;
        }

        return $choiceGenerator($entity->getInstance(), $field);
    }

    private function getBadgeCssClass($badgeSelector, $value, FieldDto $field): string
    {
        $commonBadgeCssClass = 'badge badge-pill';

        if (true === $badgeSelector) {
            $badgeType = 'badge-secondary';
        } elseif (\is_array($badgeSelector)) {
            $badgeType = $badgeSelector[$value] ?? 'badge-secondary';
        } elseif (\is_callable($badgeSelector)) {
            $badgeType = $badgeSelector($value, $field);
            if (!\in_array($badgeType, ChoiceField::VALID_BADGE_TYPES, true)) {
                throw new \RuntimeException(sprintf('The value returned by the callable passed to the "renderAsBadges()" method must be one of the following valid badge types: "%s" ("%s" given).', implode(', ', ChoiceField::VALID_BADGE_TYPES), $badgeType));
            }
        }

        $badgeTypeCssClass = empty($badgeType) ? '' : u($badgeType)->ensureStart('badge-');

        return $commonBadgeCssClass.' '.$badgeTypeCssClass;
    }
}
