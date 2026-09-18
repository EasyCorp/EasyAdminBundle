<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Extension;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\LockableInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\EventListener\LockVersionValidationListener;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * @author Ahmed EBEN HASSINE <ahmedbhs123@gmail.com>
 */
class LockVersionExtension extends AbstractTypeExtension
{
    private LockVersionValidationListener $validationListener;
    private AdminContextProviderInterface $adminContextProvider;

    public function __construct(LockVersionValidationListener $validationListener, AdminContextProviderInterface $adminContextProvider)
    {
        $this->validationListener = $validationListener;
        $this->adminContextProvider = $adminContextProvider;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $eaContext = $this->adminContextProvider->getContext();
        if (null === $eaContext || null === $eaContext->getCrud()) {
            return;
        }

        if (Crud::PAGE_EDIT !== $eaContext->getCrud()->getCurrentAction()) {
            return;
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $entity = $event->getData();
            $form = $event->getForm();

            if ($form->isRoot() && $entity instanceof LockableInterface) {
                $form->add(EA::LOCK_VERSION, HiddenType::class, [
                    'mapped' => false,
                    'data' => $entity->getLockVersion(),
                ]);
            }
        });

        $builder->addEventSubscriber($this->validationListener);
    }

    /**
     * @return iterable<string>
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
