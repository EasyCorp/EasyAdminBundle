<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\ORM\Query\Lexer;

class Escaper
{
    public const DQL_ALIAS_PREFIX = 'ea_';

    /**
     * Some words (e.g. "order") are reserved keywords in the DQL (Doctrine Query Language).
     * That's why when using entity names as DQL aliases, we need to escape
     * those reserved keywords.
     *
     * This method ensures that the given entity name can be used as a DQL alias.
     * Most of them are left unchanged (e.g. "category" or "invoice") but others
     * will include a prefix to escape them (e.g. "order" becomes "ea_order").
     */
    public static function escapeDqlAlias(string $entityName): string
    {
        if (self::isDqlReservedKeyword($entityName)) {
            return self::DQL_ALIAS_PREFIX.$entityName;
        }

        return $entityName;
    }

    /**
     * Determines if a string is a reserved keyword in DQL (Doctrine Query Language).
     */
    private static function isDqlReservedKeyword(string $string): bool
    {
        $lexer = new Lexer($string);

        $lexer->moveNext();
        $token = $lexer->lookahead;

        if (200 <= $token['type']) {
            return true;
        }

        return false;
    }
}
