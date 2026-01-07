<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldLayoutDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormLayoutFactory;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\AssetMapper\ImportMap\ImportMapRenderer;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Icons\Twig\UXIconRuntime;
use Twig\DeprecatedCallableInfo;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Defines the filters and functions used to render the bundle's templates.
 * Also injects the admin context into Twig global variables as `ea` in order
 * to be used by admin templates.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Benjamin Georgeault <git@wedgesama.fr>
 */
class EasyAdminTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
        private readonly AdminContextProviderInterface $adminContextProvider,
        private readonly ?CsrfTokenManagerInterface $csrfTokenManager,
        private readonly ?ImportMapRenderer $importMapRenderer,
        private readonly TranslatorInterface $translator,
        private ?UXIconRuntime $uxIconRuntime,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ea', [$this, 'ea']),
            new TwigFunction('ea_url', [$this, 'getAdminUrlGenerator']),
            new TwigFunction('ea_form_ealabel', null, ['node_class' => 'Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode', 'is_safe' => ['html']]),
            // deprecated functions
            new TwigFunction('ea_call_function_if_exists', [$this, 'callFunctionIfExists'], ['needs_environment' => true, 'is_safe' => ['html' => true], 'deprecation_info' => new DeprecatedCallableInfo('easycorp/easyadmin-bundle', '4.21.0', 'No alternative is provided because it\'s no longer needed thanks to the Twig guard tag.')]),
            new TwigFunction('ea_create_field_layout', [$this, 'createFieldLayout'], ['deprecation_info' => new DeprecatedCallableInfo('easycorp/easyadmin-bundle', '4.8.0', 'No alternative is provided because it\'s no longer needed thanks to the new rendering engine')]),
            new TwigFunction('ea_csrf_token', [$this, 'renderCsrfToken'], ['deprecation_info' => new DeprecatedCallableInfo('easycorp/easyadmin-bundle', '4.21.0', 'No alternative is provided because it\'s no longer needed thanks to the Twig guard tag.')]),
            new TwigFunction('ea_importmap', [$this, 'renderImportmap'], ['is_safe' => ['html'], 'deprecation_info' => new DeprecatedCallableInfo('easycorp/easyadmin-bundle', '4.21.0', 'No alternative is provided because it\'s no longer needed thanks to the Twig guard tag.')]),
            new TwigFunction('ea_ux_icon', [$this, 'renderIcon'], ['is_safe' => ['html'], 'deprecation_info' => new DeprecatedCallableInfo('easycorp/easyadmin-bundle', '4.21.0', 'No alternative is provided because it\'s no longer needed thanks to the Twig guard tag.')]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('ea_flatten_array', [$this, 'flattenArray']),
            new TwigFilter('ea_filesize', [$this, 'fileSize']),
            new TwigFilter('ea_as_string', [$this, 'representAsString']),
            // deprecated filters
            new TwigFilter('ea_apply_filter_if_exists', [$this, 'applyFilterIfExists'], ['needs_environment' => true, 'deprecation_info' => new DeprecatedCallableInfo('easycorp/easyadmin-bundle', '4.21.0', 'No alternative is provided because it\'s no longer needed thanks to the Twig guard tag.')]),
        ];
    }

    public function getGlobals(): array
    {
        return ['ea' => $this->adminContextProvider];
    }

    public function ea(): ?AdminContextInterface
    {
        return $this->adminContextProvider->getContext();
    }

    /**
     * Transforms ['a' => 'foo', 'b' => ['c' => ['d' => 7]]] into ['a' => 'foo', 'b[c][d]' => 7]
     * It's useful to submit nested arrays (e.g. query string parameters) as form fields.
     *
     * @param mixed[]     $array
     * @param string|null $parentKey
     *
     * @return mixed[]
     */
    public function flattenArray(/* array */ $array, /* ?string */ $parentKey = null): array
    {
        if (!\is_array($array)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.27.0',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$array',
                __METHOD__,
                '"array"',
                \gettype($array)
            );
        }
        if (!\is_string($parentKey) && null !== $parentKey) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.27.0',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$parentKey',
                __METHOD__,
                '"string" or "null"',
                \gettype($parentKey)
            );
        }

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

        if (0 === $bytes) {
            return '0B';
        }

        $factor = (int) floor(log($bytes) / log(1024));
        $factor = min($factor, \count($size) - 1);

        $scaledValue = (int) ($bytes / (1024 ** $factor));

        return sprintf('%d%s', $scaledValue, $size[$factor]);
    }

    /**
     * Code adapted from https://stackoverflow.com/a/48606773/2804294 (License: CC BY-SA 3.0).
     *
     * @return mixed
     *
     * @throws RuntimeError when twig runtime can't find the specified filter
     */
    public function applyFilterIfExists(Environment $environment, mixed $value, string $filterName, mixed ...$filterArguments)
    {
        /**
         * @var TwigFilter|null $filter
         */
        $filter = $environment->getFilter($filterName);
        if (null === $filter || false === $filter) {
            return $value;
        }

        $callback = $filter->getCallable();
        if (\is_callable($callback)) {
            return \call_user_func($callback, $value, ...$filterArguments);
        }

        if (\is_array($callback) && 2 === \count($callback)) {
            /** @var class-string $runtimeClass */
            $runtimeClass = array_shift($callback);
            $callback = [$environment->getRuntime($runtimeClass), array_pop($callback)];
            if (!\is_callable($callback)) {
                throw new RuntimeError(sprintf('Unable to load runtime for filter: "%s"', $filterName));
            }

            return \call_user_func($callback, $value, ...$filterArguments);
        }

        throw new RuntimeError(sprintf('Invalid callback for filter: "%s"', $filterName));
    }

    public function representAsString(mixed $value, string|callable|null $toStringMethod = null): string
    {
        if (null !== $toStringMethod) {
            if (\is_callable($toStringMethod)) {
                return $toStringMethod($value, $this->translator);
            }

            $callable = [$value, $toStringMethod];
            if (!\is_callable($callable) || !method_exists($value, $toStringMethod)) {
                throw new \RuntimeException(sprintf('The method "%s()" does not exist or is not callable in the value of type "%s"', $toStringMethod, \is_object($value) ? $value::class : \gettype($value)));
            }

            return \call_user_func($callable);
        }

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
            if ($value instanceof TranslatableInterface) {
                return $value->trans($this->translator);
            }

            if ($value instanceof \Stringable) {
                return (string) $value;
            }

            if (method_exists($value, 'getId')) {
                return sprintf(
                    '%s #%s',
                    // remove null bytes from class name (this happens in anonymous classes)
                    str_replace("\0", '', $value::class),
                    $value->getId()
                );
            }

            return sprintf(
                '%s #%s',
                // remove null bytes from class name (this happens in anonymous classes)
                str_replace("\0", '', $value::class),
                hash('xxh32', (string) spl_object_id($value))
            );
        }

        return '';
    }

    /**
     * @return mixed
     */
    public function callFunctionIfExists(Environment $environment, string $functionName, mixed ...$functionArguments)
    {
        if (null === $function = $environment->getFunction($functionName)) {
            return '';
        }

        $callback = $function->getCallable();
        if (\is_callable($callback)) {
            return \call_user_func($callback, ...$functionArguments);
        }

        if (\is_array($callback) && 2 === \count($callback)) {
            /** @var class-string $runtimeClass */
            $runtimeClass = array_shift($callback);
            $callback = [$environment->getRuntime($runtimeClass), array_pop($callback)];
            if (!\is_callable($callback)) {
                throw new RuntimeError(sprintf('Unable to load runtime for function: "%s"', $functionName));
            }

            return \call_user_func($callback, ...$functionArguments);
        }

        throw new RuntimeError(sprintf('Invalid callback for function: "%s"', $functionName));
    }

    /**
     * @param array<string, mixed> $queryParameters
     */
    public function getAdminUrlGenerator(array $queryParameters = []): AdminUrlGeneratorInterface
    {
        return $this->serviceLocator->get(AdminUrlGenerator::class)->setAll($queryParameters);
    }

    /**
     * Needed to avoid errors when calling 'csrf_token()' in Twig templates of applications that disabled CSRF protection.
     */
    public function renderCsrfToken(string $tokenId): string
    {
        try {
            return $this->csrfTokenManager?->getToken($tokenId)?->getValue() ?? '';
        } catch (\Exception) {
            return '';
        }
    }

    public function createFieldLayout(?FieldCollection $fieldDtos): FieldLayoutDto
    {
        return FormLayoutFactory::createFromFieldDtos($fieldDtos);
    }

    /**
     * We need to recreate the 'importmap()' Twig function from Symfony because calling it
     * via 'ea_call_function_if_exists('importmap', '...')' doesn't work.
     *
     * @param string|array<string>       $entryPoint
     * @param array<string, string|true> $attributes
     */
    public function renderImportmap(string|array $entryPoint = 'app', array $attributes = []): string
    {
        if ('' === $entryPoint || [] === $entryPoint || null === $this->importMapRenderer) {
            return '';
        }

        return $this->importMapRenderer->render($entryPoint, $attributes);
    }

    /**
     * We need to recreate the 'ux_icon()' Twig function from Symfony because calling it
     * via 'ea_call_function_if_exists('ux_icon', '...')' doesn't work.
     *
     * @param array<string, string|bool|int|float> $attributes
     */
    public function renderIcon(string $name, array $attributes = []): string
    {
        if ('' === $name || null === $this->uxIconRuntime) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"  stroke="#f00" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"><title>You are not seeing any icon because you are using custom icons (instead of the built-in FontAwesome icons) and don\'t have the Symfony UX Icons package installed in your application. Run "composer require symfony/ux-icons" and reload this page.</title><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>';
        }

        return $this->uxIconRuntime->renderIcon($name, $attributes);
    }
}
