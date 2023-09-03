<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Intl;


/**
 * Copied from https://github.com/twigphp/intl-extra/blob/2.x/src/IntlExtension.php
 * (c) Fabien Potencier - MIT License.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface IntlFormatterInterface
{
    public function formatCurrency($amount, string $currency, array $attrs = [], ?string $locale = null): string;

    public function formatNumber(
        $number,
        array $attrs = [],
        string $style = 'decimal',
        string $type = 'default',
        ?string $locale = null
    ): string;

    /**
     * @param \DateTimeZone|string|false|null $timezone The target timezone, null to use the default, false to leave unchanged
     */
    public function formatDateTime(
        ?\DateTimeInterface $date,
        ?string $dateFormat = 'medium',
        ?string $timeFormat = 'medium',
        string $pattern = '',
        $timezone = null,
        string $calendar = 'gregorian',
        ?string $locale = null
    ): ?string;

    /**
     * @param \DateTimeZone|string|false|null $timezone The target timezone, null to use the default, false to leave unchanged
     */
    public function formatDate(
        ?\DateTimeInterface $date,
        ?string $dateFormat = 'medium',
        string $pattern = '',
        $timezone = null,
        string $calendar = 'gregorian',
        ?string $locale = null
    ): ?string;

    /**
     * @param \DateTimeZone|string|false|null $timezone The target timezone, null to use the default, false to leave unchanged
     */
    public function formatTime(
        ?\DateTimeInterface $date,
        ?string $timeFormat = 'medium',
        string $pattern = '',
        $timezone = null,
        string $calendar = 'gregorian',
        ?string $locale = null
    ): ?string;
}
