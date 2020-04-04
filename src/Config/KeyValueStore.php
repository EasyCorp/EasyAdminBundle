<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

/**
 * Code adapted from https://github.com/adbario/php-dot-notation/blob/2.x/src/Dot.php
 * Copyright (c) Riku Särkinen <riku@adbar.io> - MIT License.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class KeyValueStore
{
    private $map;

    private function __construct(array $keyValueMap)
    {
        $this->map = $keyValueMap;
    }

    public static function new(array $keyValuePairs = []): self
    {
        return new self($keyValuePairs);
    }

    public function isEmpty(): bool
    {
        return 0 === \count($this->map);
    }

    public function has(string $key): bool
    {
        if (empty($this->map)) {
            return false;
        }

        $items = $this->map;
        if (\array_key_exists($key, $items)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (!\is_array($items) || !\array_key_exists($segment, $items)) {
                return false;
            }

            $items = $items[$segment];
        }

        return true;
    }

    public function get(string $key, $default = null)
    {
        if (\array_key_exists($key, $this->map)) {
            return $this->map[$key];
        }

        if (false === strpos($key, '.')) {
            return $default;
        }

        $items = $this->map;
        foreach (explode('.', $key) as $segment) {
            if (!\is_array($items) || !\array_key_exists($segment, $items)) {
                return $default;
            }

            $items = &$items[$segment];
        }

        return $items;
    }

    public function set(string $key, $value): void
    {
        $items = &$this->map;
        foreach (explode('.', $key) as $segment) {
            if (!isset($items[$segment]) || !\is_array($items[$segment])) {
                $items[$segment] = [];
            }

            $items = &$items[$segment];
        }

        $items = $value;
    }

    public function setIfNotSet(string $key, $value): void
    {
        if (!$this->has($key)) {
            $this->set($key, $value);
        }
    }

    public function setAll(array $keyValuePairs): void
    {
        foreach ($keyValuePairs as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function delete(string $key): void
    {
        if (\array_key_exists($key, $this->map)) {
            unset($this->map[$key]);

            return;
        }

        $items = &$this->map;
        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);

        foreach ($segments as $segment) {
            if (!isset($items[$segment]) || !\is_array($items[$segment])) {
                return;
            }

            $items = &$items[$segment];
        }

        unset($items[$lastSegment]);
    }

    public function all(): array
    {
        return $this->map;
    }
}
