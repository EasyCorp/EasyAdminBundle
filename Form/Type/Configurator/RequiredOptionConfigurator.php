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
 * This configurator is applied to any type for which the "required" option has
 * not been configured yet. The implementation uses a FormTypeGuesserInterface
 * instance in order to guess the "required" value, for instance from PHPdoc,
 * validation or doctrine metadata, etc.
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
