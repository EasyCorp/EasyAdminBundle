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
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ArrayConfigurator implements FieldConfiguratorInterface
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return ArrayField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $field->setFormTypeOptionIfNotSet('entry_type', TextType::class);
        $field->setFormTypeOptionIfNotSet('allow_add', true);
        $field->setFormTypeOptionIfNotSet('allow_delete', true);
        $field->setFormTypeOptionIfNotSet('delete_empty', true);
        $field->setFormTypeOptionIfNotSet('entry_options.label', false);

        $value = $field->getValue();
        if (!is_countable($value) || 0 === \count($value)) {
            $field->setTemplateName('label/empty');

            return;
        }

        if (Crud::PAGE_INDEX === $context->getCrud()->getCurrentPage()) {
            $values = $field->getValue();
            if ($values instanceof PersistentCollection) {
                $values = array_map(static fn($item): string => (string)$item, $values->getValues());
            }

            $field->setFormattedValue(u(', ')->join($values)->toString());
        } else if (Crud::PAGE_DETAIL === $context->getCrud()->getCurrentPage() && is_iterable($field->getValue())) {
            $field->setValue(
                array_map(
                    fn(mixed $value) =>
                        $value instanceof TranslatableInterface
                            ? $value->trans($this->translator)
                            : $value,
                    iterator_to_array($field->getValue())
                )
            );
        }
    }
}
