<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\Type;

use Doctrine\ORM\Mapping\ClassMetadata;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Custom form type that deals with some of the logic used to render the
 * forms used to create and edit EasyAdmin entities.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EasyAdminFormType extends AbstractType
{
    /** @var Configurator */
    private $configurator;

    /** @var array */
    private $config;

    /**
     * @param Configurator $configurator
     * @param array        $config
     */
    public function __construct(Configurator $configurator, array $config)
    {
        $this->configurator = $configurator;
        $this->config = $config;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entity = $options['entity'];
        $view = $options['view'];
        $entityConfig = $this->configurator->getEntityConfiguration($entity);
        $entityProperties = $entityConfig[$view]['fields'];

        foreach ($entityProperties as $name => $metadata) {
            $formFieldOptions = $metadata['type_options'];

            if ('association' === $metadata['type']) {
                // *-to-many associations are not supported yet
                $toManyAssociations = array(ClassMetadata::ONE_TO_MANY, ClassMetadata::MANY_TO_MANY);
                if (in_array($metadata['associationType'], $toManyAssociations)) {
                    continue;
                }

                // supported associations are displayed using advanced JavaScript widgets
                $formFieldOptions['attr']['data-widget'] = 'select2';
            }

            if ('collection' === $metadata['fieldType']) {
                if (!isset($formFieldOptions['allow_add'])) {
                    $formFieldOptions['allow_add'] = true;
                }

                if (!isset($formFieldOptions['allow_delete'])) {
                    $formFieldOptions['allow_delete'] = true;
                }

                if (version_compare(\Symfony\Component\HttpKernel\Kernel::VERSION, '2.5.0', '>=')) {
                    if (!isset($formFieldOptions['delete_empty'])) {
                        $formFieldOptions['delete_empty'] = true;
                    }
                }
            } elseif ('checkbox' === $metadata['fieldType'] && !isset($formFieldOptions['required'])) {
                $formFieldOptions['required'] = false;
            }

            $formFieldOptions['attr']['field_type'] = $metadata['fieldType'];
            $formFieldOptions['attr']['field_css_class'] = $metadata['class'];
            $formFieldOptions['attr']['field_help'] = $metadata['help'];

            $builder->add($name, $metadata['fieldType'], $formFieldOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $configurator = $this->configurator;
        $config = $this->config;

        $resolver
            ->setDefaults(array(
                'allow_extra_fields' => true,
                'data_class' => function (Options $options) use ($configurator) {
                    $entity = $options['entity'];
                    $entityConfig = $configurator->getEntityConfiguration($entity);

                    return $entityConfig['class'];
                },
            ))
            ->setNormalizers(array(
                'attr' => function (Options $options, $value) use ($config) {
                    $formCssClass = array_reduce($config['design']['form_theme'], function ($previousClass, $formTheme) {
                        return sprintf('theme-%s %s', strtolower(str_replace('.html.twig', '', basename($formTheme))), $previousClass);
                    });

                    return array_replace_recursive(array(
                        'class' => $formCssClass,
                        'id' => $options['view'].'-form',
                    ), $value);
                },
            ))
            ->setRequired(array('entity', 'view'));
    }

    // BC for SF < 2.7
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'easyadmin';
    }
}
