<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Util;

// Code copied from https://github.com/selvinortiz/dot/blob/master/src/SelvinOrtiz/Dot/Dot.php
// Copyright (c) Selvin Ortiz - MIT License
final class DotArray
{
    public static function has(array $array, $key): bool
    {
        if (false !== strpos($key, '.') && \count(($keys = explode('.', $key)))) {
            foreach ($keys as $key) {
                if (!\array_key_exists($key, $array)) {
                    return false;
                }

                $array = $array[$key];
            }

            return true;
        }

        return \array_key_exists($key, $array);
    }

    public static function get(array $array, string $key, $default = null)
    {
        if (false !== strpos($key, '.') && \count(($keys = explode('.', $key)))) {
            foreach ($keys as $key) {
                if (!\array_key_exists($key, $array)) {
                    return $default;
                }

                $array = $array[$key];
            }

            return $array;
        }

        return \array_key_exists($key, $array) ? $array[$key] : $default;
    }

    public static function set(array &$array, string $key, $value): void
    {
        if (false !== strpos($key, '.') && ($keys = explode('.', $key)) && \count($keys)) {
            while (\count($keys) > 1) {
                $key = array_shift($keys);

                if (!isset($array[$key]) || !\is_array($array[$key])) {
                    $array[$key] = [];
                }

                $array = &$array[$key];
            }

            $array[array_shift($keys)] = $value;
        } else {
            $array[$key] = $value;
        }
    }
}
