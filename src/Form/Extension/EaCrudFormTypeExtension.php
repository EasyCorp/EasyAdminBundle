<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Extension;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Extension that injects EasyAdmin related information in the view used to
 * render the form.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EaCrudFormTypeExtension extends AbstractTypeExtension
{
    private $applicationContextProvider;

    public function __construct(ApplicationContextProvider $applicationContextProvider)
    {
        $this->applicationContextProvider = $applicationContextProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['ea_crud_form']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (null === $this->applicationContextProvider->getContext()) {
            return;
        }

        $view->vars['ea_crud_form'] = [
            'form_panel' => $form->getConfig()->getAttribute('ea_form_panel'),
            'form_tab' => $form->getConfig()->getAttribute('ea_form_tab'),
            'ea_property' => $form->getConfig()->getAttribute('ea_property'),
            'ea_entity' => $form->getConfig()->getAttribute('ea_entity'),
        ];

        //$formField->setAttribute('ea_form_panel', $currentFormPanel);
        //$formField->setAttribute('ea_form_tab', $currentFormTab);

        return;

        // TODO: check the following code
        /*
        $easyadmin = $request->attributes->get('easyadmin');
        $entity = $easyadmin['entity'];
        $action = $easyadmin['view'];
        $fields = $entity[$action]['fields'] ?? [];
        $filters = $easyadmin['filters'] ?? [];
        $view->vars['easyadmin'] = [
            'field' => null,
            'form_group' => $form->getConfig()->getAttribute('easyadmin_form_group'),
            'form_tab' => $form->getConfig()->getAttribute('easyadmin_form_tab'),
            'filters' => $filters,
        ];
        */

        /*
         * Checks if current form view is direct child on the topmost form
         * (ie. this form view`s field exists in easyadmin configuration)
         */
        /*
        if (null !== $view->parent && null === $view->parent->parent) {
            $view->vars['easyadmin']['field'] = $fields[$view->vars['name']] ?? null;
        }
        */
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
