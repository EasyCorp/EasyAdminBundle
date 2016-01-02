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

use JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\Configurator\TypeConfiguratorInterface;
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

    /** @var TypeConfiguratorInterface[] */
    private $configurators;

    /**
     * @param Configurator                $configurator
     * @param array                       $config
     * @param TypeConfiguratorInterface[] $configurators
     */
    public function __construct(Configurator $configurator, array $config, array $configurators = array())
    {
        $this->configurator = $configurator;
        $this->config = $config;
        $this->configurators = $configurators;
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

            // Configure options using the list of registered type configurators:
            foreach ($this->configurators as $configurator) {
                if ($configurator->supports($metadata['fieldType'], $formFieldOptions, $metadata)) {
                    $formFieldOptions = $configurator->configure($name, $formFieldOptions, $metadata, $builder);
                }
            }

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
            $resolver->setNormalizers(array('attr' => $this->getAttributesNormalizer()));
        } else {
            $resolver->setNormalizer('attr', $this->getAttributesNormalizer());
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
     * @return \Closure
     */
    private function getAttributesNormalizer()
    {
        return function (Options $options, $value) {
            return array_replace(array(
                'id' => $options['view'].'-form',
            ), $value);
        };
    }

    /**
     * It returns the FQCN of the given short type name.
     * Example: 'text' -> 'Symfony\Component\Form\Extension\Core\Type\TextType'
     *
     * @param string $shortType
     *
     * @return string
     */
    private function getFormTypeFqcn($shortType)
    {
        $builtinTypes = array(
            'birthday', 'button', 'checkbox', 'choice', 'collection', 'country',
            'currency', 'datetime', 'date', 'email', 'entity', 'file', 'form',
            'hidden', 'integer', 'language', 'locale', 'money', 'number',
            'password', 'percent', 'radio', 'range', 'repeated', 'reset',
            'search', 'submit', 'textarea', 'text', 'time', 'timezone', 'url',
        );

        if (!in_array($shortType, $builtinTypes)) {
            return $shortType;
        }

        $irregularTypeFqcn = array(
            'entity' => 'Symfony\\Bridge\\Doctrine\\Form\\Type\\EntityType',
            'datetime' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\DateTimeType',
        );

        if (array_key_exists($shortType, $irregularTypeFqcn)) {
            return $irregularTypeFqcn[$shortType];
        }

        return sprintf('Symfony\\Component\\Form\\Extension\\Core\\Type\\%sType', ucfirst($shortType));
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
}
