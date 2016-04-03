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

    /** @var TypeConfiguratorInterface[] */
    private $configurators;

    /**
     * @param Configurator                $configurator
     * @param TypeConfiguratorInterface[] $configurators
     */
    public function __construct(Configurator $configurator, array $configurators = array())
    {
        $this->configurator = $configurator;
        $this->configurators = $configurators;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entity = $options['entity'];
        $view = $options['view'];
        $entityConfig = $this->configurator->getEntityConfig($entity);
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
                    $entityConfig = $configurator->getEntityConfig($entity);

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
                'id' => sprintf('%s-%s-form', $options['view'], strtolower($options['entity'])),
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
        $supportedTypes = array(
            // Symfony's built-in types
            'birthday' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\BirthdayType',
            'button' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ButtonType',
            'checkbox' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType',
            'choice' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType',
            'collection' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\CollectionType',
            'country' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\CountryType',
            'currency' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\CurrencyType',
            'datetime' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\DateTimeType',
            'date' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\DateType',
            'email' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\EmailType',
            'entity' => 'Symfony\\Bridge\\Doctrine\\Form\\Type\\EntityType',
            'file' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\FileType',
            'form' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\FormType',
            'hidden' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\HiddenType',
            'integer' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\IntegerType',
            'language' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\LanguageType',
            'locale' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\LocaleType',
            'money' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\MoneyType',
            'number' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\NumberType',
            'password' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\PasswordType',
            'percent' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\PercentType',
            'radio' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\RadioType',
            'range' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\RangeType',
            'repeated' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\RepeatedType',
            'reset' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ResetType',
            'search' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\SearchType',
            'submit' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\SubmitType',
            'textarea' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType',
            'text' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
            'time' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TimeType',
            'timezone' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TimezoneType',
            'url' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\UrlType',
            // EasyAdmin custom types
            'easyadmin_autocomplete' => 'JavierEguiluz\\Bundle\\EasyAdminBundle\\Form\\Type\\EasyAdminAutocompleteType',
            // Popular third-party bundles types
            'ckeditor' => 'Ivory\\CKEditorBundle\\Form\\Type\\CKEditorType',
            'vich_file' => 'Vich\\UploaderBundle\\Form\\Type\\VichFileType',
            'vich_image' => 'Vich\\UploaderBundle\\Form\\Type\\VichImageType',
        );

        if (array_key_exists($shortType, $supportedTypes)) {
            return $supportedTypes[$shortType];
        }

        return $shortType;
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
