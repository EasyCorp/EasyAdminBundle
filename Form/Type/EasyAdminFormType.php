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
use Symfony\Component\Form\FormTypeGuesserInterface;
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

    /** @var FormTypeGuesserInterface */
    private $guesser;

    /**
     * @param Configurator             $configurator
     * @param array                    $config
     * @param FormTypeGuesserInterface $guesser
     */
    public function __construct(Configurator $configurator, array $config, FormTypeGuesserInterface $guesser)
    {
        $this->configurator = $configurator;
        $this->config = $config;
        $this->guesser = $guesser;
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

            if (!isset($formFieldOptions['required'])) {
                $formFieldOptions['required'] = $this->guesser->guessRequired($builder->getOption('data_class'), $name)->getValue();
            }

            $formFieldOptions['attr']['field_type'] = $metadata['fieldType'];
            $formFieldOptions['attr']['field_css_class'] = $metadata['class'];
            $formFieldOptions['attr']['field_help'] = $metadata['help'];

            $formFieldType = $this->useLegacyFormComponent() ? $metadata['fieldType'] : $this->getFormTypeFqcn($metadata['fieldType']);
            $builder->add($name, $formFieldType, $formFieldOptions);
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
            ->setRequired(array('entity', 'view'));

        if ($this->useLegacyFormComponent()) {
            $resolver->setNormalizers(array('attr' => function (Options $options, $value) use ($config) {
                $formCssClass = array_reduce($config['design']['form_theme'], function ($previousClass, $formTheme) {
                    return sprintf('theme-%s %s', strtolower(str_replace('.html.twig', '', basename($formTheme))), $previousClass);
                });

                return array_replace_recursive(array(
                    'class' => $formCssClass,
                    'id' => $options['view'].'-form',
                ), $value);
            }));
        } else {
            $resolver->setNormalizer('attr', function (Options $options, $value) use ($config) {
                $formCssClass = array_reduce($config['design']['form_theme'], function ($previousClass, $formTheme) {
                    return sprintf('theme-%s %s', strtolower(str_replace('.html.twig', '', basename($formTheme))), $previousClass);
                });

                return array_replace_recursive(array(
                    'class' => $formCssClass,
                    'id' => $options['view'].'-form',
                ), $value);
            });
        }
    }

    // BC for SF < 2.7
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * It returns the FQCN of the given short type.
     * Example: 'text' -> 'Symfony\Component\Form\Extension\Core\Type\TextType'
     *
     * @param string $shortType
     *
     * @return string
     */
    private function getFormTypeFqcn($shortType)
    {
        $typeNames = array(
            'base', 'birthday', 'button', 'checkbox', 'choice', 'collection',
            'country', 'currency', 'datetime', 'date', 'email', 'file', 'form',
            'hidden', 'integer', 'language', 'money', 'number', 'password',
            'percent', 'radio', 'repeated', 'reset', 'search', 'submit',
            'textarea', 'text', 'time', 'timezone', 'url',
        );

        if (!in_array($shortType, $typeNames)) {
            return $shortType;
        }

        // take into account the irregular class name for 'datetime' type
        $typeClassName = 'datetime' === $shortType ? 'DateTime' : ucfirst($shortType);
        $typeFqcn = sprintf('Symfony\\Component\\Form\\Extension\\Core\\Type\\%sType', $typeClassName);

        return $typeFqcn;
    }

    /**
     * Returns true if the legacy Form component is being used by the application.
     *
     * @return bool
     */
    private function useLegacyFormComponent()
    {
        return false === class_exists('Symfony\\Component\\Form\\Util\\StringUtil');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'easyadmin';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
