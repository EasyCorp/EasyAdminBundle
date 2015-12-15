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

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entity = $options['entity'];
        $view = $options['view'];
        $entityConfig = $this->configurator->getEntityConfiguration($entity);
        $entityProperties = $entityConfig[$view]['fields'];

        foreach ($entityProperties as $name => $metadata) {
            $formFieldOptions = $metadata['type_options'];

            if ('association' === $metadata['type']) {
                if ($metadata['associationType'] & ClassMetadata::TO_MANY) {
                    $formFieldOptions['attr']['multiple'] = true;
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

                // The "delete_empty" option exists as of Sf >= 2.5
                if (class_exists('Symfony\\Component\\Form\\FormErrorIterator')) {
                    if (!isset($formFieldOptions['delete_empty'])) {
                        $formFieldOptions['delete_empty'] = true;
                    }
                }
            } elseif ('checkbox' === $metadata['fieldType'] && !isset($formFieldOptions['required'])) {
                $formFieldOptions['required'] = false;
            }

            if (!isset($formFieldOptions['required'])) {
                if (null !== $guessRequired = $this->guesser->guessRequired($builder->getOption('data_class'), $name)) {
                    $formFieldOptions['required'] = $guessRequired->getValue();
                }
            }

            // Configure "placeholder" option for entity fields
            if ('association' === $metadata['type']
                && ($metadata['associationType'] & ClassMetadata::TO_ONE)
                && !isset($formFieldOptions[$placeHolderOptionName = $this->getPlaceholderOptionName()])
                && false === $formFieldOptions['required']
            ) {
                $formFieldOptions[$placeHolderOptionName] = 'form.label.empty_value';
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
            $resolver->setNormalizers(array('attr' => $this->getAttributesNormalizer($config)));
        } else {
            $resolver->setNormalizer('attr', $this->getAttributesNormalizer($config));
        }
    }

    // BC for SF < 2.7
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
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

    /**
     * Returns a closure normalizing the form html attributes.
     *
     * @param array $config
     *
     * @return \Closure
     */
    private function getAttributesNormalizer(array $config)
    {
        return function (Options $options, $value) use ($config) {
            $formCssClass = array_reduce($config['design']['form_theme'], function ($previousClass, $formTheme) {
                return sprintf('theme-%s %s', strtolower(str_replace('.html.twig', '', basename($formTheme))), $previousClass);
            });

            return array_replace_recursive(array(
                'class' => $formCssClass,
                'id' => $options['view'].'-form',
            ), $value);
        };
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
            'birthday', 'button', 'checkbox', 'choice', 'collection', 'country',
            'currency', 'datetime', 'date', 'email', 'entity', 'file', 'form',
            'hidden', 'integer', 'language', 'locale', 'money', 'number',
            'password', 'percent', 'radio', 'range', 'repeated', 'reset',
            'search', 'submit', 'textarea', 'text', 'time', 'timezone', 'url',
        );

        if (!in_array($shortType, $typeNames)) {
            return $shortType;
        }

        if ('entity' === $shortType) {
            return 'Symfony\\Bridge\\Doctrine\\Form\\Type\\EntityType';
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
     * BC for Sf < 2.6
     *
     * The "empty_value" option in the types "choice", "date", "datetime" and "time"
     * was deprecated in 2.6 and replaced by a new option "placeholder".
     *
     * @return string
     */
    private function getPlaceholderOptionName()
    {
        return defined('Symfony\\Component\\Form\\Extension\\Validator\\Constraints\\Form::NOT_SYNCHRONIZED_ERROR')
            ? 'placeholder'
            : 'empty_value';
    }
}
