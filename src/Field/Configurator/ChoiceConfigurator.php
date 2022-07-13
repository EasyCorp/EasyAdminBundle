<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Translation\TranslatableChoiceMessage;
use EasyCorp\Bundle\EasyAdminBundle\Translation\TranslatableChoiceMessageCollection;
use function Symfony\Component\String\u;
use function Symfony\Component\Translation\t;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ChoiceConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return ChoiceField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $areChoicesTranslatable = $field->getCustomOption(ChoiceField::OPTION_USE_TRANSLATABLE_CHOICES);
        $isExpanded = $field->getCustomOption(ChoiceField::OPTION_RENDER_EXPANDED);
        $isMultipleChoice = $field->getCustomOption(ChoiceField::OPTION_ALLOW_MULTIPLE_CHOICES);

        $choices = $this->getChoices($field->getCustomOption(ChoiceField::OPTION_CHOICES), $entityDto, $field);

        if ($areChoicesTranslatable) {
            $field->setFormTypeOptionIfNotSet('choices', array_keys($choices));
            $field->setFormTypeOptionIfNotSet('choice_label', fn ($value) => $choices[$value]);
        } else {
            $field->setFormTypeOptionIfNotSet('choices', $choices);
        }
        $field->setFormTypeOptionIfNotSet('multiple', $isMultipleChoice);
        $field->setFormTypeOptionIfNotSet('expanded', $isExpanded);

        if ($isExpanded && ChoiceField::WIDGET_AUTOCOMPLETE === $field->getCustomOption(ChoiceField::OPTION_WIDGET)) {
            throw new \InvalidArgumentException(sprintf('The "%s" choice field wants to be displayed as an autocomplete widget and as an expanded list of choices at the same time, which is not possible. Use the renderExpanded() and renderAsNativeWidget() methods to change one of those options.', $field->getProperty()));
        }

        if (null === $field->getCustomOption(ChoiceField::OPTION_WIDGET)) {
            $field->setCustomOption(ChoiceField::OPTION_WIDGET, $isExpanded ? ChoiceField::WIDGET_NATIVE : ChoiceField::WIDGET_AUTOCOMPLETE);
        }

        if (ChoiceField::WIDGET_AUTOCOMPLETE === $field->getCustomOption(ChoiceField::OPTION_WIDGET)) {
            $field->setFormTypeOption('attr.data-ea-widget', 'ea-autocomplete');
            $field->setDefaultColumns($isMultipleChoice ? 'col-md-8 col-xxl-6' : 'col-md-6 col-xxl-5');
        }

        $field->setFormTypeOptionIfNotSet('placeholder', '');

        // the value of this form option must be a string to properly propagate it as an HTML attribute value
        $field->setFormTypeOption('attr.data-ea-autocomplete-render-items-as-html', $field->getCustomOption(ChoiceField::OPTION_ESCAPE_HTML_CONTENTS) ? 'false' : 'true');

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
        $flippedChoices = $areChoicesTranslatable ? $choices : array_flip($choices);
        // $value is a scalar for single selections and an array for multiple selections
        foreach ((array) $fieldValue as $selectedValue) {
            if (null !== $selectedChoice = $flippedChoices[$selectedValue] ?? null) {
                if ($selectedValue instanceof TranslatableInterface) {
                    $choiceValue = $selectedValue;
                } else {
                    $choiceValue = t(
                        $selectedChoice,
                        $translationParameters,
                        $translationDomain
                    );
                }
                $selectedChoices[] = new TranslatableChoiceMessage(
                    $choiceValue,
                    $isRenderedAsBadge ? $this->getBadgeCssClass($badgeSelector, $selectedValue, $field) : null
                );
            }
        }
        $field->setFormattedValue(new TranslatableChoiceMessageCollection($selectedChoices, $isRenderedAsBadge));
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
        $commonBadgeCssClass = 'badge';

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

        $badgeTypeCssClass = empty($badgeType) ? '' : u($badgeType)->ensureStart('badge-')->toString();

        return $commonBadgeCssClass.' '.$badgeTypeCssClass;
    }
}
