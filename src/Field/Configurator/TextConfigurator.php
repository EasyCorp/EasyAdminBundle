<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\ActionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class TextConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDtoInterface $field, EntityDtoInterface $entityDto): bool
    {
        return \in_array($field->getFieldFqcn(), [TextField::class, TextareaField::class], true);
    }

    public function configure(FieldDtoInterface $field, EntityDtoInterface $entityDto, AdminContext $context): void
    {
        if (TextareaField::class === $field->getFieldFqcn()) {
            $field->setFormTypeOptionIfNotSet('attr.rows', $field->getCustomOption(TextareaField::OPTION_NUM_OF_ROWS));
            $field->setFormTypeOptionIfNotSet('attr.data-ea-textarea-field', true);
        }

        if (null === $value = $field->getValue()) {
            return;
        }

        if (!\is_string($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new \RuntimeException(
                sprintf(
                    'The value of the "%s" field of the entity with ID = "%s" can\'t be converted into a string, so it cannot be represented by a TextField or a TextareaField.',
                    $field->getProperty(),
                    $entityDto->getPrimaryKeyValue()
                )
            );
        }

        $renderAsHtml = true === $field->getCustomOption(TextField::OPTION_RENDER_AS_HTML);
        $stripTags = true === $field->getCustomOption(TextField::OPTION_STRIP_TAGS);
        if ($renderAsHtml) {
            $formattedValue = (string)$field->getValue();
        } elseif ($stripTags) {
            $formattedValue = strip_tags((string)$field->getValue());
        } else {
            $formattedValue = htmlspecialchars((string)$field->getValue(), \ENT_NOQUOTES, null, false);
        }

        $configuredMaxLength = $field->getCustomOption(TextField::OPTION_MAX_LENGTH);
        // when contents are rendered as HTML, "max length" option is ignored to prevent
        // truncating contents in the middle of an HTML tag, which messes the entire backend
        if (!$renderAsHtml) {
            $isDetailAction = ActionInterface::DETAIL === $context->getCrud()->getCurrentAction();
            $defaultMaxLength = $isDetailAction ? \PHP_INT_MAX : 64;
            $formattedValue = u($formattedValue)->truncate($configuredMaxLength ?? $defaultMaxLength, '…')->toString();
        }

        $field->setFormattedValue($formattedValue);
    }
}
