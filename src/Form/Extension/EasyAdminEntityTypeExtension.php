<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Extension;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Util\FormTypeHelper;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class EasyAdminEntityTypeExtension extends AbstractTypeExtension
{
    private $configManager;

    public function __construct(ConfigManagerInterface $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $config = $this->configManager->getEntityConfigByClass($options['class']);
        if (null === $config && ($options['allow_new'] || $options['allow_edit'])) {
            throw new \InvalidArgumentException(sprintf('The configuration of the "%s" entity is not available to allow edit or create new items in "%s" field.', $options['class'], $form->getName()));
        }

        $view->vars['_easyadmin_entity'] = [
            'name' => $config['name'],
            'allow_new' => $options['allow_new'],
            'allow_edit' => $options['allow_edit'],
            'primary_key_field_name' => $config['primary_key_field_name'],
            'uniqueid' => uniqid($view->vars['id'].'_', false),
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $allowNew = function (Options $options) {
            if ($config = $this->configManager->getEntityConfigByClass($options['class'])) {
                return !\in_array('new', $config['disabled_actions'], true);
            }

            return false;
        };
        $allowEdit = function (Options $options) {
            if ($config = $this->configManager->getEntityConfigByClass($options['class'])) {
                return !\in_array('edit', $config['disabled_actions'], true) && !$options['multiple'];
            }

            return false;
        };

        $allowEditNormalizer = function (Options $options, $allowEdit) {
            if ($allowEdit && $options['multiple']) {
                throw new \InvalidArgumentException('Edit multiple items is not supported.');
            }

            return $allowEdit;
        };

        $resolver
            ->setDefaults([
                'allow_new' => $allowNew,
                'allow_edit' => $allowEdit,
            ])
            ->setAllowedTypes('allow_new', 'bool')
            ->setAllowedTypes('allow_edit', 'bool')
            ->setNormalizer('allow_edit', $allowEditNormalizer)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FormTypeHelper::getTypeClass('entity');
    }
}
