<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use function Symfony\Component\String\u;
use Twig\Markup;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CommonPostConfigurator implements FieldConfiguratorInterface
{
    private $adminContextProvider;
    private $charset;

    public function __construct(AdminContextProvider $adminContextProvider, string $charset)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->charset = $charset;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        // this configurator applies to all kinds of properties
        return true;
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        // The form pages use directly the "normal" value.
        // So for these ones, it's useless to compute the formatted value.
        if (false === \in_array($context->getCrud()->getCurrentPage(), [Crud::PAGE_NEW, Crud::PAGE_EDIT], true)) {
            $formattedValue = $this->buildFormattedValueOption($field->getFormattedValue(), $field, $entityDto);
            $field->setFormattedValue($formattedValue);
        }

        $this->updateFieldTemplate($field);
    }

    /**
     * If the callable return a string (and only in that case), we encapsulate it in a Twig Markup.
     * Like that, the potential html and js elements are directly rendered (without escaping).
     */
    private function buildFormattedValueOption($value, FieldDto $field, EntityDto $entityDto)
    {
        if (null === $callable = $field->getFormatValueCallable()) {
            return $value;
        }

        $formatted = $callable($value, $entityDto->getInstance());

        return \is_string($formatted) ? new Markup($formatted, $this->charset) : $formatted;
    }

    private function updateFieldTemplate(FieldDto $field): void
    {
        $usesEasyAdminTemplate = u($field->getTemplatePath())->startsWith('@EasyAdmin/');
        $isBooleanField = BooleanField::class === $field->getFieldFqcn();
        $isNullValue = null === $field->getFormattedValue();
        $isEmpty = is_countable($field->getFormattedValue()) ? 0 === \count($field->getFormattedValue()) : false;

        $adminContext = $this->adminContextProvider->getContext();
        if ($usesEasyAdminTemplate && $isNullValue && !$isBooleanField) {
            $field->setTemplatePath($adminContext->getTemplatePath('label/null'));
        }

        if ($usesEasyAdminTemplate && $isEmpty) {
            $field->setTemplatePath($adminContext->getTemplatePath('label/empty'));
        }
    }
}
