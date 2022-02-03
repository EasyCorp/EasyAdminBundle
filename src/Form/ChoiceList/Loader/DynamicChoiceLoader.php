<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\ChoiceList\Loader;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
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
    private array $choices = [];
    private bool $cached = false;
    private ?ArrayChoiceList $choiceList = null;

    public function loadChoiceList(?callable $value = null): ChoiceListInterface
    {
        if (null === $this->choiceList || !$this->cached) {
            $this->choiceList = new ArrayChoiceList(array_combine($this->choices, $this->choices));
            $this->cached = true;
        }

        return $this->choiceList;
    }

    public function loadChoicesForValues(array $values, $value = null): array
    {
        if ($this->choices !== $values) {
            $this->cached = false;
        }

        return $this->choices = $values;
    }

    public function loadValuesForChoices(array $choices, $value = null): array
    {
        return $choices;
    }
}
