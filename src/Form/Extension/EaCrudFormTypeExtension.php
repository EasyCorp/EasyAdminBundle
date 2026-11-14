<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Extension;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FormVarsDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Bridge\Doctrine\Form\Type\DoctrineType;
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
    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('ea_crud_form');
        $resolver->define('ea_vars')->allowedTypes(FormVarsDto::class);

        $resolver->setDeprecated('ea_crud_form', 'easycorp/easyadmin-bundle', 'The "%name%" form option is deprecated since EasyAdmin 4.8.0 and will be removed in 5.0.0. Use the "ea_vars" option instead (e.g. instead of "ea_crud_form.ea_field", use "ea_vars.field").');
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var FieldDto|null $fieldDto */
        $fieldDto = $form->getConfig()->getAttribute('ea_field');

        if ($this->mustEscapeEntityChoiceLabels($form, $fieldDto, $options)) {
            foreach ($view->children as $name => $choiceView) {
                // the placeholder of non-required radio buttons is written by the developer, not an entity
                $isPlaceholder = 'placeholder' === $name && '' === ($choiceView->vars['value'] ?? null);
                if (!$isPlaceholder) {
                    $choiceView->vars['label_html'] = false;
                }
            }
        }

        if (null === $this->adminContextProvider->getContext()) {
            return;
        }

        $view->vars['ea_vars'] = new FormVarsDto(
            fieldDto: $form->getConfig()->getAttribute('ea_field'),
            entityDto: $form->getConfig()->getAttribute('ea_entity')
        );

        // TODO: remove this entire variable in EasyAdmin 5.0.0
        $view->vars['ea_crud_form'] = [
            'form_panel' => $form->getConfig()->getAttribute('ea_form_panel'),
            'form_fieldset' => $form->getConfig()->getAttribute('ea_form_fieldset'),
            'form_tab' => $form->getConfig()->getAttribute('ea_form_tab'),
            'ea_field' => $form->getConfig()->getAttribute('ea_field'),
            'ea_entity' => $form->getConfig()->getAttribute('ea_entity'),
        ];
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    /**
     * EasyAdmin enables 'label_html' in all fields to allow HTML in their labels (see
     * CommonPostConfigurator) but Symfony also applies it to the checkboxes and radio
     * buttons of expanded choice fields. When those choices are entities, their labels
     * are stored data (e.g. the result of __toString()), so they must be escaped unless
     * the field explicitly renders them as HTML.
     *
     * @param array<string, mixed> $options
     */
    private function mustEscapeEntityChoiceLabels(FormInterface $form, ?FieldDto $fieldDto, array $options): bool
    {
        if (null === $fieldDto || true !== ($options['expanded'] ?? false)) {
            return false;
        }

        if (false === $fieldDto->getCustomOption(AssociationField::OPTION_ESCAPE_HTML_CONTENTS)) {
            return false;
        }

        for ($formType = $form->getConfig()->getType(); null !== $formType; $formType = $formType->getParent()) {
            if ($formType->getInnerType() instanceof DoctrineType) {
                return true;
            }
        }

        return false;
    }
}
