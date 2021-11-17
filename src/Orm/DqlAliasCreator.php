<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\ORM\Query\Lexer;

class DqlAliasCreator
{
    public const PREFIX = 'ea_';

    /**
     * Creates a valid alias for entity names which can be used in DQL queries.
     *
     * Defaults to the entity name itself (e.g. "category") but adds a prefix
     * if the entity name is a reserved keyword in the Doctrine Query Language
     * (e.g. "order" becomes "ea_order").
     */
    public static function create(string $entityName): string
    {
        if (self::isReservedKeyword($entityName)) {
            return self::PREFIX.$entityName;
        }

        return $entityName;
    }

    /**
     * Determines if a string is a reserved keyword in Doctrine Query Language.
     */
    private static function isReservedKeyword(string $string): bool
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
