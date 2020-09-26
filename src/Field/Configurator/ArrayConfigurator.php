<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ArrayConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return ArrayField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $field->setFormTypeOption('entry_type', TextType::class);
        $field->setFormTypeOptionIfNotSet('allow_add', true);
        $field->setFormTypeOptionIfNotSet('allow_delete', true);
        $field->setFormTypeOptionIfNotSet('delete_empty', true);
        $field->setFormTypeOptionIfNotSet('entry_options.label', false);

        $value = $field->getValue();
        if (!is_countable($value) || 0 === \count($value)) {
            $field->setTemplateName('label/empty');

            return;
        }

        if (null !== $value && Crud::PAGE_INDEX === $context->getCrud()->getCurrentPage()) {
            $values = $field->getValue();
            if ($values instanceof PersistentCollection) {
                $values = array_map(static function ($item) { return (string) $item; }, $values->getValues());
            }

            $field->setFormattedValue(u(', ')->join($values));
        }
    }
}
