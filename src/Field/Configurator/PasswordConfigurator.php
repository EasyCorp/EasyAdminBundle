<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\PasswordField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final class PasswordConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return PasswordField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $hashPasswordCallable = $field->getCustomOption(PasswordField::OPTION_HASH_PASSWORD);

        if (null !== $hashPasswordCallable) {
            $field->setFormTypeOption('mapped', false);
            
            $builderDecorator = function (FormBuilderInterface $formBuilder) use ($hashPasswordCallable, $field) {
                $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($hashPasswordCallable, $field) {
                    $plainPassword = $event->getData();
                    
                    if (null !== $plainPassword && '' !== $plainPassword) {
                        $hashedPassword = $hashPasswordCallable($plainPassword);
                        
                        $entity = $event->getForm()->getParent()->getData();
                        $propertyName = $field->getProperty();
                        
                        $propertyAccessor = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessorBuilder()
                            ->enableExceptionOnInvalidIndex()
                            ->getPropertyAccessor();
                            
                        $propertyAccessor->setValue($entity, $propertyName, $hashedPassword);
                    }
                });
            };
            
            $field->setFormTypeOption('builder_callable', $builderDecorator);
        }
    }
}
