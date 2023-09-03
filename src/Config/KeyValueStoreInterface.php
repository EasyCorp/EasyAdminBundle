<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Config;


/**
 * Code adapted from https://github.com/adbario/php-dot-notation/blob/2.x/src/Dot.php
 * Copyright (c) Riku Särkinen <riku@adbar.io> - MIT License.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface KeyValueStoreInterface
{
    public function isEmpty(): bool;

    public function has(string $key): bool;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function setIfNotSet(string $key, mixed $value): void;

    public function setAll(array $keyValuePairs): void;

    public function delete(string $key): void;

    public function all(): array;
}
