<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldLayoutDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldLayoutDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminTabType;

final class FieldLayoutFactory implements FieldLayoutFactoryInterface
{
    private function __construct()
    {
    }

    public static function createFromFieldDtos(FieldCollection|null $fieldDtos): FieldLayoutDtoInterface
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
        /** @var FieldDtoInterface $fieldDto */
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

    private static function isTabField(FieldDtoInterface $fieldDto): bool
    {
        return EasyAdminTabType::class === $fieldDto->getFormType();
    }
}
