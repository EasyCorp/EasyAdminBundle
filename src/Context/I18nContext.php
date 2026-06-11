<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextDirection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Registry\TemplateRegistry;

/**
 * Encapsulates internationalization and template-related data for the admin context.
 * Don't use this class directly; use @AdminContext class instead.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class I18nContext
{
    public function __construct(
        private readonly I18nDto $i18nDto,
        private readonly TemplateRegistry $templateRegistry,
    ) {
    }

    public function getI18n(): I18nDto
    {
        return $this->i18nDto;
    }

    public function getTemplatePath(string $templateName): string
    {
        return $this->templateRegistry->get($templateName);
    }

    /**
     * Creates an I18nContext instance suitable for testing.
     *
     * @param array<string, mixed> $translationParameters
     */
    public static function forTesting(
        string $locale = 'en',
        string $textDirection = TextDirection::LTR,
        string $translationDomain = 'messages',
        array $translationParameters = [],
        ?TemplateRegistry $templateRegistry = null,
    ): self {
        return new self(
            new I18nDto($locale, $textDirection, $translationDomain, $translationParameters),
            $templateRegistry ?? TemplateRegistry::new()
        );
    }
}
