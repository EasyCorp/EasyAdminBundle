<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

/**
 * Most of the code of this class is copied from https://github.com/adbario/php-dot-notation/blob/2.x/src/Dot.php
 * Copyright (c) Riku Särkinen <riku@adbar.io> - MIT License.
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
        return empty($this->map);
    }

    public function has($key): bool
    {
        $keys = (array) $key;

        if (!$this->map || $keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $items = $this->map;

            if ($this->exists($items, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (!\is_array($items) || !$this->exists($items, $segment)) {
                    return false;
                }

                $items = $items[$segment];
            }
        }

        return true;
    }

    public function get(string $key, $default = null)
    {
        if (null === $key) {
            return $this->map;
        }

        if ($this->exists($this->map, $key)) {
            return $this->map[$key];
        }

        if (false === strpos($key, '.')) {
            return $default;
        }

        $items = $this->map;

        foreach (explode('.', $key) as $segment) {
            if (!\is_array($items) || !$this->exists($items, $segment)) {
                return $default;
            }

            $items = &$items[$segment];
        }

        return $items;
    }

    public function set(string $keys, $value): void
    {
        if (\is_array($keys)) {
            foreach ($keys as $key => $value) {
                $this->set($key, $value);
            }

            return;
        }

        $items = &$this->map;

        foreach (explode('.', $keys) as $key) {
            if (!isset($items[$key]) || !\is_array($items[$key])) {
                $items[$key] = [];
            }

            $items = &$items[$key];
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

    public function delete(string $keys): void
    {
        $keys = (array) $keys;

        foreach ($keys as $key) {
            if ($this->exists($this->map, $key)) {
                unset($this->map[$key]);

                continue;
            }

            $items = &$this->map;
            $segments = explode('.', $key);
            $lastSegment = array_pop($segments);

            foreach ($segments as $segment) {
                if (!isset($items[$segment]) || !\is_array($items[$segment])) {
                    continue 2;
                }

                $items = &$items[$segment];
            }

            unset($items[$lastSegment]);
        }
    }

    public function all(): array
    {
        return $this->map;
    }

    private function exists($array, $key)
    {
        return \array_key_exists($key, $array);
    }
}
