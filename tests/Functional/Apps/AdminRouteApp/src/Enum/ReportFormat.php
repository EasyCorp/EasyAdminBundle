<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Enum;

enum ReportFormat: string
{
    case Csv = 'csv';
    case Xlsx = 'xlsx';
}
