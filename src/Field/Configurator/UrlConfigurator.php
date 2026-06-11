<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\Validator\Constraints\Url;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class UrlConfigurator implements FieldConfiguratorInterface
{
    /**
     * These schemes are considered dangerous and make the URL to not be rendered as a clickable link:
     *
     * `javascript:` - executes script in the document's origin on click.
     * `data:` - `data:text/html,...` renders attacker HTML (and inline script) in the clicked tab.
     * `vbscript:` - legacy IE / some embedded browsers execute VBScript.
     */
    private const DANGEROUS_SCHEMES = ['javascript', 'data', 'vbscript', 'file'];

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return UrlField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $field->setFormTypeOptionIfNotSet('attr.inputmode', 'url');
        $field->setFormTypeOptionIfNotSet('default_protocol', $field->getCustomOption(UrlField::OPTION_DEFAULT_PROTOCOL));

        $allowedProtocols = $field->getCustomOption(UrlField::OPTION_ALLOWED_PROTOCOLS);
        if (\is_array($allowedProtocols)) {
            $constraints = $field->getFormTypeOption('constraints') ?? [];
            // the `requireTld` option was introduced in symfony/validator 7.1 with a
            // deprecated default of `false`; pass it explicitly to silence the
            // deprecation while preserving the historical behavior on older versions
            if (property_exists(Url::class, 'requireTld')) {
                $constraints[] = new Url(protocols: $allowedProtocols, requireTld: false);
            } else {
                $constraints[] = new Url(protocols: $allowedProtocols);
            }
            $field->setFormTypeOption('constraints', $constraints);
        }

        $url = (string) $field->getValue();
        // browsers strip leading whitespace and control bytes before parsing the scheme,
        // so the same normalization must happen here before matching against the denylist
        $normalizedUrl = ltrim($url, "\0..\x20");

        // scheme characters per RFC 3986: scheme = `ALPHA *( ALPHA / DIGIT / "+" / "-" / "." )`
        $isUnsafe = 1 === preg_match('#^([a-z][a-z0-9+.\-]*):#i', $normalizedUrl, $matches)
            && \in_array(strtolower($matches[1]), self::DANGEROUS_SCHEMES, true);
        $field->setCustomOption(UrlField::OPTION_IS_UNSAFE, $isUnsafe);

        $prettyUrl = str_replace(['http://www.', 'https://www.', 'http://', 'https://'], '', $url);
        $prettyUrl = rtrim($prettyUrl, '/');

        if (Action::INDEX === $context->getCrud()->getCurrentAction()) {
            $prettyUrl = u($prettyUrl)->truncate(32, '…')->toString();
        }

        $field->setFormattedValue($prettyUrl);
    }
}
