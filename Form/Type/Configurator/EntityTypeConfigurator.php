<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;

/**
 * This configurator is applied to any form field of type 'association' and is
 * used to configure lots of their features (for example whether we should use
 * a JavaScript widget to display their contents).
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EntityTypeConfigurator implements TypeConfiguratorInterface
{
    /** @var FormTypeGuesserInterface */
    private $guesser;

    /**
     * @param FormTypeGuesserInterface $guesser
     */
    public function __construct(FormTypeGuesserInterface $guesser)
    {
        $this->guesser = $guesser;
    }

    /**
     * {@inheritdoc}
     */
    public function configure($name, array $options, array $metadata, FormConfigInterface $parentConfig)
    {
        if (!isset($options['class'])) {
            $guessedOptions = $this->guesser->guessType($parentConfig->getDataClass(), $name)->getOptions();
            $options['class'] = $guessedOptions['class'];
            $options['multiple'] = $guessedOptions['multiple'];
            $options['em'] = $guessedOptions['em'];
        }

        if ($metadata['associationType'] & ClassMetadata::TO_MANY) {
            $options['attr']['multiple'] = true;
        }

        // Supported associations are displayed using advanced JavaScript widgets
        $options['attr']['data-widget'] = 'select2';

        // Configure "placeholder" option for entity fields
        if (($metadata['associationType'] & ClassMetadata::TO_ONE)
            && !isset($options[$placeHolderOptionName = $this->getPlaceholderOptionName()])
            && isset($options['required']) && false === $options['required']
        ) {
            $options[$placeHolderOptionName] = 'label.form.empty_value';
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata)
    {
        return 'entity' === $type && 'association' === $metadata['type'];
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
