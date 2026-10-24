<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Defines the filters and functions used to render the bundle's templates.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Benjamin Georgeault <git@wedgesama.fr>
 */
class EasyAdminTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
        private readonly AdminContextProviderInterface $adminContextProvider,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ea', [$this, 'ea']),
            new TwigFunction('ea_url', [$this, 'getAdminUrlGenerator']),
            new TwigFunction('ea_form_ealabel', null, ['node_class' => 'Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode', 'is_safe' => ['html']]),
            new TwigFunction('ea_uid', static fn (string $prefix = 'ea-'): string => $prefix.Ulid::generate()),
        ];
    }

    public function getTests(): array
    {
        return [
            // 'foo is not empty' can't be used with TranslatableMessage values because it triggers
            // a deprecation ("Method TranslatableMessage::__toString() is deprecated"); this test
            // checks the same without casting the value to a string
            new TwigTest('ea_is_not_empty', static fn (mixed $value): bool => false !== $value && null !== $value && '' !== $value),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('ea_flatten_array', [$this, 'flattenArray']),
            new TwigFilter('ea_filesize', [$this, 'fileSize']),
            new TwigFilter('ea_as_string', [$this, 'representAsString']),
            new TwigFilter('ea_html_attrs', [$this, 'processHtmlAttributes']),
            new TwigFilter('ea_filetype_icon', [$this, 'getFiletypeIcon']),
            new TwigFilter('ea_force_download', [$this, 'forceFileDownload']),
        ];
    }

    public function ea(): ?AdminContextInterface
    {
        return $this->adminContextProvider->getContext();
    }

    /**
     * Transforms ['a' => 'foo', 'b' => ['c' => ['d' => 7]]] into ['a' => 'foo', 'b[c][d]' => 7]
     * It's useful to submit nested arrays (e.g. query string parameters) as form fields.
     *
     * @param array<string|int, mixed> $array
     *
     * @return array<string|int, mixed>
     */
    public function flattenArray(array $array, ?string $parentKey = null): array
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

    /**
     * Processes an array of HTML attributes, translating any TranslatableInterface values.
     * This is needed because Twig Components don't accept non-scalar attribute values.
     *
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    public function processHtmlAttributes(array $attributes): array
    {
        $processed = [];
        foreach ($attributes as $name => $value) {
            $processed[$name] = $value instanceof TranslatableInterface
                ? $value->trans($this->translator)
                : $value;
        }

        return $processed;
    }

    public function getFiletypeIcon(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, \PATHINFO_EXTENSION));

        return match ($extension) {
            'mp3', 'wav', 'ogg', 'flac', 'aac', 'wma', 'm4a', 'opus', 'aiff' => 'audio',
            'mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm', 'm4v', 'mpeg', 'mpg' => 'video',
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'ico', 'tiff', 'tif', 'avif', 'heic', 'heif' => 'image',
            'pdf' => 'pdf',
            'doc', 'dot', 'docx', 'dotx', 'odt', 'rtf', 'txt' => 'document',
            'xls', 'xlsx', 'xltx', 'xltm', 'ods', 'csv' => 'spreadsheet',
            'ppt', 'pps', 'pot',  'pptx', 'potx', 'potm', 'odp', 'key' => 'presentation',
            'htm', 'html', 'xhtml', 'js', 'ts', 'jsx', 'tsx', 'php', 'py', 'java', 'c', 'cpp', 'h', 'cs', 'rb', 'go', 'rs', 'swift', 'kt', 'sh', 'bash', 'json', 'xml', 'yaml', 'yml', 'toml', 'ini', 'sql', 'css', 'scss', 'less' => 'code',
            'svg', 'ai', 'eps', 'svgz' => 'vector',
            'zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz', 'tgz' => 'zip',
            default => 'generic',
        };
    }

    /**
     * Returns true for uploaded files that browsers render inline and that can
     * execute embedded scripts (e.g. HTML or SVG documents). EasyAdmin forces a
     * download for these types instead of linking to them inline, to prevent a
     * stored XSS when uploads are served from the same origin as the backend.
     */
    public function forceFileDownload(string $filename): bool
    {
        // checking the extension (and not the real MIME type) is enough and correct here:
        // browsers and web servers decide whether to render a file inline based on the
        // Content-Type derived from its extension, not from its actual contents. So the
        // stored extension is exactly what determines the risk. Besides, the real MIME
        // type was already resolved from the contents at upload time (via guessExtension())
        // and reading the file again at render time would be costly and impossible for
        // remote storage (e.g. Flysystem URLs).
        $extension = strtolower(pathinfo($filename, \PATHINFO_EXTENSION));

        return \in_array($extension, [
            'htm', 'html', 'xhtml', 'xht', 'shtml', 'shtm', 'mhtml', 'mht',
            'svg', 'svgz',
            'xml', 'xsl', 'xslt',
        ], true);
    }

    public function fileSize(int $bytes): string
    {
        $size = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        if (0 === $bytes) {
            return '0 B';
        }

        $factor = (int) floor(log($bytes) / log(1024));
        $factor = min($factor, \count($size) - 1);

        $scaledValue = $bytes / (1024 ** $factor);

        if (0 === $factor) {
            return sprintf('%d %s', $scaledValue, $size[$factor]);
        }

        $scaledValue = round($scaledValue, 1);
        $format = 0.0 === fmod($scaledValue, 1.0) ? '%d %s' : '%.1f %s';

        return sprintf($format, $scaledValue, $size[$factor]);
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
                try {
                    return (string) $value;
                } catch (\Throwable) {
                    // e.g. the __toString() method of a new entity created for the
                    // collection form prototype may access uninitialized properties
                    return '';
                }
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
     * @param array<string, mixed> $queryParameters
     */
    public function getAdminUrlGenerator(array $queryParameters = []): AdminUrlGeneratorInterface
    {
        return $this->serviceLocator->get(AdminUrlGeneratorInterface::class)->setAll($queryParameters);
    }
}
