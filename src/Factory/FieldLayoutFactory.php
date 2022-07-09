<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldLayoutDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminTabType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @internal and @experimental don't use this in your own apps
 */
final class FieldLayoutFactory
{
    private function __construct()
    {
    }

    public static function createFromFieldDtos(FieldCollection|null $fieldDtos): FieldLayoutDto
    {
        if (null === $fieldDtos) {
            return new FieldLayoutDto();
        }

        $hasTabs = false;
        foreach ($fieldDtos as $fieldDto) {
            if (self::isTabField($fieldDto)) {
                $hasTabs = true;
                break;
            }
        }

        $tabs = [];
        $fields = [];
        $currentTab = null;
        /** @var FieldDto $fieldDto */
        foreach ($fieldDtos as $fieldDto) {
            if (self::isTabField($fieldDto)) {
                $currentTab = $fieldDto;
                $tabs[$fieldDto->getUniqueId()] = $fieldDto;
            } else {
                if ($hasTabs) {
                    $fields[$currentTab->getUniqueId()][] = $fieldDto;
                } else {
                    $fields[] = $fieldDto;
                }
            }
        }

        return new FieldLayoutDto($fields, $tabs);
    }

    private static function isTabField(FieldDto $fieldDto): bool
    {
        return EasyAdminTabType::class === $fieldDto->getFormType();
    }
}
