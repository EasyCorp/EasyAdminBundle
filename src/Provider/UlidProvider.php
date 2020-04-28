<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

/**
 * Code copied from https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Uid/Ulid.php
 * (c) Nicolas Grekas <p@tchwork.com> - MIT License
 * TODO: replace this class by the Symfony Uid component when it's released as stable.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
final class UlidProvider
{
    private static $time = '';
    private static $rand = [];
    private const BASE10 = [
        '' => '0123456789',
        0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
    ];

    public static function new(): string
    {
        $time = microtime(false);
        $time = substr($time, 11).substr($time, 2, 3);

        if ($time !== self::$time) {
            $r = unpack('nr1/nr2/nr3/nr4/nr', random_bytes(10));
            $r['r1'] |= ($r['r'] <<= 4) & 0xF0000;
            $r['r2'] |= ($r['r'] <<= 4) & 0xF0000;
            $r['r3'] |= ($r['r'] <<= 4) & 0xF0000;
            $r['r4'] |= ($r['r'] <<= 4) & 0xF0000;
            unset($r['r']);
            self::$rand = array_values($r);
            self::$time = $time;
        } elseif ([0xFFFFF, 0xFFFFF, 0xFFFFF, 0xFFFFF] === self::$rand) {
            usleep(100);

            return self::new();
        } else {
            for ($i = 3; $i >= 0 && 0xFFFFF === self::$rand[$i]; --$i) {
                self::$rand[$i] = 0;
            }

            ++self::$rand[$i];
        }

        if (\PHP_INT_SIZE >= 8) {
            $time = base_convert($time, 10, 32);
        } else {
            $time = bin2hex(self::fromBase($time, self::BASE10));
            $time = sprintf('%s%04s%04s',
                base_convert(substr($time, 0, 2), 16, 32),
                base_convert(substr($time, 2, 5), 16, 32),
                base_convert(substr($time, 7, 5), 16, 32)
            );
        }

        return strtr(sprintf('%010s%04s%04s%04s%04s',
            $time,
            base_convert(self::$rand[0], 10, 32),
            base_convert(self::$rand[1], 10, 32),
            base_convert(self::$rand[2], 10, 32),
            base_convert(self::$rand[3], 10, 32)
        ), 'abcdefghijklmnopqrstuv', 'ABCDEFGHJKMNPQRSTVWXYZ');
    }

    private static function fromBase(string $digits, array $map): string
    {
        $base = \strlen($map['']);
        $count = \strlen($digits);
        $bytes = [];

        while ($count) {
            $quotient = [];
            $remainder = 0;

            for ($i = 0; $i !== $count; ++$i) {
                $carry = ($bytes ? $digits[$i] : $map[$digits[$i]]) + $remainder * $base;

                if (\PHP_INT_SIZE >= 8) {
                    $digit = $carry >> 16;
                    $remainder = $carry & 0xFFFF;
                } else {
                    $digit = $carry >> 8;
                    $remainder = $carry & 0xFF;
                }

                if ($digit || $quotient) {
                    $quotient[] = $digit;
                }
            }

            $bytes[] = $remainder;
            $count = \count($digits = $quotient);
        }

        return pack(\PHP_INT_SIZE >= 8 ? 'n*' : 'C*', ...array_reverse($bytes));
    }
}
