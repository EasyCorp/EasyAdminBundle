<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Generator;

use EasyCorp\Bundle\EasyAdminBundle\Generator\LabelGenerator;
use PHPUnit\Framework\TestCase;

class LabelGeneratorTest extends TestCase
{
    /**
     * @dataProvider humanizeDataProvider
     */
    public function testHumanize(string $expectedLabel, string $input): void
    {
        $this->assertSame($expectedLabel, LabelGenerator::humanize($input));
    }

    public static function humanizeDataProvider(): iterable
    {
        yield ['Name', 'name'];
        yield ['First Name', 'firstName'];
        yield ['Project Manager', 'projectManager'];
        yield ['Address City', 'address.city'];
        yield ['First Name', 'first_name'];
        yield ['ID', 'id'];
        yield ['URL', 'url'];
        yield ['UUID', 'UUID'];
        yield ['Uuid', 'Uuid'];
        yield ['HTML', 'HTML'];
        yield ['Save And Return', 'saveAndReturn'];
        yield ['Batch Delete', 'batchDelete'];
        yield ['Address City Name', 'address.city.name'];
        // the "U R L" result looks a bit ugly but generating a better result would
        // require certain changes in the label generator and would make it slower
        // for all cases in exchange of fixing a rare edge case
        yield ['Product U R L', 'product.URL'];
    }
}
