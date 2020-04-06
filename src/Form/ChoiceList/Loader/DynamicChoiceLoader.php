<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\ChoiceList\Loader;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

/**
 * Loads the choice list from the submitted values.
 *
 * This allows adding more <option> to the <select> input dynamically.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class DynamicChoiceLoader implements ChoiceLoaderInterface
{
    private $choices = [];
    private $cached = false;
    private $choiceList;

    /**
     * {@inheritdoc}
     */
    public function loadChoiceList($value = null)
    {
        if (null === $this->choiceList || !$this->cached) {
            $this->choiceList = new ArrayChoiceList(array_combine($this->choices, $this->choices));
            $this->cached = true;
        }

        return $this->choiceList;
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoicesForValues(array $values, $value = null): array
    {
        if ($this->choices !== $values) {
            $this->cached = false;
        }

        return $this->choices = $values;
    }

    /**
     * {@inheritdoc}
     */
    public function loadValuesForChoices(array $choices, $value = null): array
    {
        return $choices;
    }
}
