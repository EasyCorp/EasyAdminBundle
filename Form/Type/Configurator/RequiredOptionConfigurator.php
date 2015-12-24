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

use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;

/**
 * This configurator is applied to any form field type and is used to decide
 * whether the field should be required or not.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class RequiredOptionConfigurator implements TypeConfiguratorInterface
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
        // The implementation uses a FormTypeGuesserInterface instance in order
        // to guess the "required" value from different sources (PHPdoc,
        // validation or doctrine metadata, etc.)
        $guessed = $this->guesser->guessRequired($parentConfig->getDataClass(), $name);

        if (null !== $guessed) {
            $options['required'] = $guessed->getValue();
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata)
    {
        return !isset($options['required']);
    }
}
