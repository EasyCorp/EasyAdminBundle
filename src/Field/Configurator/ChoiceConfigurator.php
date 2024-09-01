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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use function Symfony\Component\String\u;
use function Symfony\Component\Translation\t;
use Symfony\Component\Translation\TranslatableMessage;
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
        $areChoicesTranslatable = true === $field->getCustomOption(ChoiceField::OPTION_USE_TRANSLATABLE_CHOICES);
        $choicesSupportTranslatableInterface = false;
        $isExpanded = true === $field->getCustomOption(ChoiceField::OPTION_RENDER_EXPANDED);
        $isMultipleChoice = true === $field->getCustomOption(ChoiceField::OPTION_ALLOW_MULTIPLE_CHOICES);
        // Initialize the variable since we are using it outside the $enumsAreSupported condition
        $allChoicesAreEnums = false;

        $choices = $this->getChoices($field->getCustomOption(ChoiceField::OPTION_CHOICES), $entityDto, $field);

        // using a more precise check like 'function_exists('enum_exists');' messes with IDEs like PhpStorm
        $enumsAreSupported = \PHP_VERSION_ID >= 80100;

        if (null === $choices) {
            $choices = [];
        }

        // support for enums
        if ($enumsAreSupported) {
            $elementIsEnum = array_unique(array_map(static function ($element): bool {
                return \is_object($element) && enum_exists($element::class);
            }, $choices));
            $allChoicesAreEnums = false === \in_array(false, $elementIsEnum, true);

            // if no choices are passed to the field, check if it's related to an Enum;
            // in that case, get all the possible values of the Enum (Doctrine supports only BackedEnum as enumType)
            $enumTypeClass = $field->getDoctrineMetadata()->get('enumType');
            if (0 === \count($choices) && null !== $enumTypeClass && enum_exists($enumTypeClass)) {
                $choices = $enumTypeClass::cases();
                $allChoicesAreEnums = true;
            }

            // SF 6.4 and up has native support for translatable enums, we should respect that too
            if (is_subclass_of($enumTypeClass, TranslatableInterface::class)) {
                $areChoicesTranslatable = $choicesSupportTranslatableInterface = true;
            }

            if ($allChoicesAreEnums && array_is_list($choices) && \count($choices) > 0) {
                $processedEnumChoices = [];
                foreach ($choices as $choice) {
                    $processedEnumChoices[$choice->name] = $choice;
                }

                $choices = $processedEnumChoices;

                // Update form type to be EnumType if current form type is still ChoiceType
                // Leave the form type as is if user set something else explicitly
                if (ChoiceType::class === $field->getFormType()) {
                    $field->setFormType(EnumType::class);
                }
                $field->setFormTypeOptionIfNotSet('class', $enumTypeClass);
            }
        }

        if ($areChoicesTranslatable && !$choicesSupportTranslatableInterface) {
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
        $field->setFormTypeOption('attr.data-ea-autocomplete-render-items-as-html', true === $field->getCustomOption(ChoiceField::OPTION_ESCAPE_HTML_CONTENTS) ? 'false' : 'true');

        $fieldValue = $field->getValue();
        $isIndexOrDetail = \in_array($context->getCrud()->getCurrentPage(), [Crud::PAGE_INDEX, Crud::PAGE_DETAIL], true);
        if (null === $fieldValue || !$isIndexOrDetail) {
            return;
        }

        if ($enumsAreSupported) {
            // Backed enum converted to array result in array [enum->name, enum->value] as done in the loop bellow
            // That results in grid displaying two values when single enum is a selected value
            // This makes sure we pass an array of enums as selected value when single enum is selected
            if ($fieldValue instanceof \UnitEnum) {
                $fieldValue = [$fieldValue];
            }
        }

        $badgeSelector = $field->getCustomOption(ChoiceField::OPTION_RENDER_AS_BADGES);
        $isRenderedAsBadge = null !== $badgeSelector && false !== $badgeSelector;

        $translationParameters = $context->getI18n()->getTranslationParameters();
        $translationDomain = $context->getI18n()->getTranslationDomain();
        $choiceMessages = [];
        // Translatable choice don't need to get flipped
        $flippedChoices = $areChoicesTranslatable ? $choices : array_flip($this->flatten($choices));
        foreach ((array) $fieldValue as $selectedValue) {
            $selectedValue = match (true) {
                // We check if $allChoicesAreEnums is true for enum's and choices array is generated using ->name as index
                $selectedValue instanceof \BackedEnum => $allChoicesAreEnums && $choicesSupportTranslatableInterface ? $selectedValue->name : $selectedValue->value,
                $selectedValue instanceof \UnitEnum => $selectedValue->name,
                default => $selectedValue,
            };
            if (null !== $selectedLabel = $flippedChoices[$selectedValue] ?? null) {
                if ($selectedLabel instanceof TranslatableInterface) {
                    $choiceMessage = $selectedLabel;
                } else {
                    $choiceMessage = t(
                        $selectedLabel,
                        $translationParameters,
                        $translationDomain
                    );
                }

                /** @var TranslatableMessage $choiceMessage */
                $choiceMessages[] = new TranslatableChoiceMessage(
                    $choiceMessage,
                    $isRenderedAsBadge ? $this->getBadgeCssClass($badgeSelector, $selectedValue, $field) : null
                );
            }
        }
        $field->setFormattedValue(new TranslatableChoiceMessageCollection($choiceMessages, $isRenderedAsBadge));
    }

    private function getChoices($choiceGenerator, EntityDto $entity, FieldDto $field): ?array
    {
        if (null === $choiceGenerator) {
            return null;
        }

        if (\is_array($choiceGenerator)) {
            return $choiceGenerator;
        }

        return $choiceGenerator($entity->getInstance(), $field);
    }

    private function getBadgeCssClass($badgeSelector, $value, FieldDto $field): string
    {
        $commonBadgeCssClass = 'badge';

        $badgeType = '';
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

        $badgeTypeCssClass = '' === $badgeType ? '' : u($badgeType)->ensureStart('badge-')->toString();

        return $commonBadgeCssClass.' '.$badgeTypeCssClass;
    }

    private function flatten(array $choices): array
    {
        $flattened = [];

        foreach ($choices as $label => $choice) {
            // Flatten grouped choices
            if (\is_array($choice)) {
                foreach ($choice as $subLabel => $subChoice) {
                    $flattened[$subLabel] = $subChoice;
                }
            } elseif ($choice instanceof \BackedEnum) {
                $flattened[$choice->name] = $choice->value;
            } elseif ($choice instanceof \UnitEnum) {
                $flattened[$choice->name] = $choice->name;
            } else {
                $flattened[$label] = $choice;
            }
        }

        return $flattened;
    }
}
