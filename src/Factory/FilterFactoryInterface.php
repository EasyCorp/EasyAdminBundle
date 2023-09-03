<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;


use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDtoInterface;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FilterFactoryInterface
{
    public function create(
        FilterConfigDtoInterface $filterConfig,
        FieldCollection $fields,
        EntityDtoInterface $entityDto
    ): FilterCollection;
}
