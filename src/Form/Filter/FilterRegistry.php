<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeGuesserChain;
use Symfony\Component\Form\FormTypeGuesserInterface;

/**
 * The central registry of the filter system.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class FilterRegistry
{
    private $typesMap;
    private $typeGuessers;
    private $guesser;

    public function __construct(array $typesMap, iterable $typeGuessers)
    {
        $this->typesMap = $typesMap;
        $this->typeGuessers = $typeGuessers;
    }

    /**
     * Returns a filter type by name.
     *
     * @param string $name The name of the type
     *
     * @return string The type service id
     *
     * @throws InvalidArgumentException if the type can not be retrieved
     */
    public function getType(string $name): string
    {
        if (!$this->hasType($name)) {
            throw new InvalidArgumentException(sprintf('The filter name "%s" is not registered.', $name));
        }

        return $this->typesMap[$name];
    }

    /**
     * Returns whether the given filter name is supported.
     *
     * @param string $name The name of the type
     *
     * @return bool Whether the type is supported
     */
    public function hasType(string $name): bool
    {
        return isset($this->typesMap[$name]);
    }

    /**
     * Resolves the filter type from a given form.
     *
     * @param FormInterface $form The form instance
     *
     * @return FilterInterface The resolved filter type
     *
     * @throws RuntimeException if the filter type cannot be resolved
     */
    public function resolveType(FormInterface $form): FilterInterface
    {
        $resolvedFormType = $form->getConfig()->getType();
        $filterType = $resolvedFormType->getInnerType();

        while (!$filterType instanceof FilterInterface) {
            if (null === $resolvedFormType = $resolvedFormType->getParent()) {
                throw new RuntimeException(sprintf('Filter type "%s" must implement "%s".', \get_class($form->getConfig()->getType()->getInnerType()), FilterInterface::class));
            }

            $filterType = $resolvedFormType->getInnerType();
        }

        return $filterType;
    }

    /**
     * Returns the guesser responsible for guessing filter types.
     */
    public function getTypeGuesser(): FormTypeGuesserInterface
    {
        if (null === $this->guesser) {
            $this->guesser = new FormTypeGuesserChain($this->typeGuessers);
        }

        return $this->guesser;
    }
}
