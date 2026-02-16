<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Generator;

use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class LabelGenerator
{
    /**
     * Converts a given string into another string ready to be used as a label for the public UI.
     * Examples:
     *
     *     'name' -> 'Name'
     *     'firstName' -> 'First Name'
     *     'projectManager' -> 'Project Manager'
     *     'address.city' -> 'Address City'
     *     'id' -> 'ID'
     */
    public static function humanize(string $string): string
    {
        $uString = u($string);
        $upperString = $uString->upper()->toString();

        // this prevents humanizing all-uppercase labels (e.g. 'UUID' -> 'U u i d')
        // and other special labels which look better in uppercase
        if ($uString->toString() === $upperString || \in_array($upperString, ['ID', 'URL'], true)) {
            return $upperString;
        }

        return $uString
            ->replaceMatches('/([A-Z])/', '_$1')
            ->replaceMatches('/[_.\s]+/', ' ')
            ->trim()
            ->lower()
            ->title(true)
            ->toString();
    }
}
