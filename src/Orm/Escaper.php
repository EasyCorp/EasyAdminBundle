<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\TokenType;

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
        // backwards compat for when $token changed from array to object
        // https://github.com/doctrine/lexer/pull/79
        /** @phpstan-ignore-next-line */
        $type = \is_array($token) ? $token['type'] : $token->type;

        // Doctrine ORM 3.x changed this and the type is now a TokenType object
        if ($type instanceof TokenType) {
            $type = $type->value;
        }

        // tokens that are not valid identifiers (e.g. T_OPEN_PARENTHESIS, T_EQUALS) are < 100
        // see https://www.doctrine-project.org/projects/doctrine-lexer/en/3.1/dql-parser.html
        if ($type < 100) {
            throw new \RuntimeException(sprintf('The "%s" string is not a valid identifier in Doctrine queries.', $string));
        }

        // tokens that are keywords (e.g. T_AND, T_JOIN, T_ORDER) are >= 200
        // see https://www.doctrine-project.org/projects/doctrine-lexer/en/3.1/dql-parser.html
        return $type >= 200;
    }
}
