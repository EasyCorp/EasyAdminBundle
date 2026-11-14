<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Extension;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
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
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Extension that injects EasyAdmin related information in the view used to
 * render the form.
 *
 * @phpstan-type InputAddon array{icon: ?string, html: ?TranslatableInterface, text: ?string}
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EaCrudFormTypeExtension extends AbstractTypeExtension
{
    // form widgets that don't render a single-line input, so they can't display input addons
    private const BLOCK_PREFIXES_WITHOUT_ADDONS = ['textarea', 'checkbox', 'radio', 'file'];

    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('ea_vars')->allowedTypes(FormVarsDto::class);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var FieldDto|null $fieldDto */
        $fieldDto = $form->getConfig()->getAttribute('ea_field');

        $view->vars['ea_addons'] = $this->buildInputAddons($view, $fieldDto);

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
            fieldDto: $fieldDto,
            entityDto: $form->getConfig()->getAttribute('ea_entity')
        );
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

    /**
     * It merges the addons configured with the prepend()/append() field methods
     * with the default addons of some form types (the currency symbol of money
     * fields and the percentage symbol of percent fields) so templates can
     * render all of them in order with the same markup.
     *
     * @return array{prepend: list<InputAddon>, append: list<InputAddon>}
     */
    private function buildInputAddons(FormView $view, ?FieldDto $fieldDto): array
    {
        $addons = ['prepend' => [], 'append' => []];

        if (true === ($view->vars['compound'] ?? false)) {
            return $addons;
        }
        if ([] !== array_intersect(self::BLOCK_PREFIXES_WITHOUT_ADDONS, $view->vars['block_prefixes'] ?? [])) {
            return $addons;
        }

        $userPrepend = $fieldDto?->getCustomOption(FieldInterface::OPTION_PREPEND);
        if (\is_array($userPrepend)) {
            $addons['prepend'][] = ['icon' => $userPrepend['icon'] ?? null, 'html' => $userPrepend['html'] ?? null, 'text' => null];
        }

        // the symbol and its position are locale-dependent and only known here, after
        // Symfony's MoneyType::finishView() has computed the 'money_pattern' variable
        $moneyPattern = $view->vars['money_pattern'] ?? null;
        if (\is_string($moneyPattern) && '' !== $currencySymbol = trim(str_replace('{{ widget }}', '', $moneyPattern))) {
            if (!str_starts_with($moneyPattern, '{{')) {
                $addons['prepend'][] = ['icon' => null, 'html' => null, 'text' => $currencySymbol];
            }
            if (!str_ends_with($moneyPattern, '}}')) {
                $addons['append'][] = ['icon' => null, 'html' => null, 'text' => $currencySymbol];
            }
        }

        $percentSymbol = $view->vars['symbol'] ?? false;
        if (\is_string($percentSymbol) && '' !== $percentSymbol) {
            $addons['append'][] = ['icon' => null, 'html' => null, 'text' => $percentSymbol];
        }

        $userAppend = $fieldDto?->getCustomOption(FieldInterface::OPTION_APPEND);
        if (\is_array($userAppend)) {
            $addons['append'][] = ['icon' => $userAppend['icon'] ?? null, 'html' => $userAppend['html'] ?? null, 'text' => null];
        }

        return $addons;
    }
}
