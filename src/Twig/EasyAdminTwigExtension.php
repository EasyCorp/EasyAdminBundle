<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig;

use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Defines the filters and functions used to render the bundle's templates.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EasyAdminTwigExtension extends AbstractExtension
{
    private $serviceLocator;

    public function __construct(ServiceLocator $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('ea_url', [$this, 'getAdminUrlGenerator']),
            new TwigFunction('ea_call_function_if_exists', [$this, 'callFunctionIfExists'], ['needs_environment' => true, 'is_safe' => ['html' => true]]),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('ea_flatten_array', [$this, 'flattenArray']),
            new TwigFilter('ea_filesize', [$this, 'fileSize']),
            new TwigFilter('ea_apply_filter_if_exists', [$this, 'applyFilterIfExists'], ['needs_environment' => true]),
            new TwigFilter('ea_as_string', [$this, 'representAsString']),
        ];
    }

    /**
     * Transforms ['a' => 'foo', 'b' => ['c' => ['d' => 7]]] into ['a' => 'foo', 'b[c][d]' => 7]
     * It's useful to submit nested arrays (e.g. query string parameters) as form fields.
     */
    public function flattenArray($array, $parentKey = null)
    {
        $flattenedArray = [];

        foreach ($array as $flattenedKey => $value) {
            $flattenedKey = null !== $parentKey ? sprintf('%s[%s]', $parentKey, $flattenedKey) : $flattenedKey;

            if (\is_array($value)) {
                $flattenedArray = array_merge($flattenedArray, $this->flattenArray($value, $flattenedKey));
            } else {
                $flattenedArray[$flattenedKey] = $value;
            }
        }

        return $flattenedArray;
    }

    public function fileSize(int $bytes): string
    {
        $size = ['B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];
        $factor = (int) floor(log($bytes) / log(1024));

        return (int) ($bytes / (1024 ** $factor)).@$size[$factor];
    }

    // Code adapted from https://stackoverflow.com/a/48606773/2804294 (License: CC BY-SA 3.0)
    public function applyFilterIfExists(Environment $environment, $value, string $filterName, ...$filterArguments)
    {
        if (false === $filter = $environment->getFilter($filterName)) {
            return $value;
        }

        return $filter->getCallable()($value, ...$filterArguments);
    }

    public function representAsString($value): string
    {
        if (null === $value) {
            return '';
        }

        if (\is_string($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (\is_array($value)) {
            return sprintf('Array (%d items)', \count($value));
        }

        if (\is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            if (method_exists($value, 'getId')) {
                return sprintf('%s #%s', \get_class($value), $value->getId());
            }

            return sprintf('%s #%s', \get_class($value), substr(md5(spl_object_hash($value)), 0, 7));
        }

        return '';
    }

    public function callFunctionIfExists(Environment $environment, string $functionName, ...$functionArguments)
    {
        if (false === $function = $environment->getFunction($functionName)) {
            return '';
        }

        return $function->getCallable()(...$functionArguments);
    }

    public function getAdminUrlGenerator(array $queryParameters = []): AdminUrlGenerator
    {
        return $this->serviceLocator->get(AdminUrlGenerator::class)->setAll($queryParameters);
    }
}
