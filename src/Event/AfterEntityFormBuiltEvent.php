<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author Gaël BORDAS <g.bordas@boginfo.fr>
 */
final class AfterEntityFormBuiltEvent
{
    /**
     * @var ?AdminContext
     */
    protected $context;

    /**
     * @var FormBuilderInterface
     */
    protected $form;

    public function __construct(FormBuilderInterface $form, ?AdminContext $context)
    {
        $this->form = $form;
        $this->context = $context;
    }

    public function getForm(): FormBuilderInterface
    {
        return $this->form;
    }

    public function getContext(): ?AdminContext
    {
        return $this->context;
    }
}
