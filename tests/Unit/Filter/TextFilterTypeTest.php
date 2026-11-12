<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\TextFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Test\TypeTestCase;

class TextFilterTypeTest extends TypeTestCase
{
    #[DataProvider('submit')]
    public function test(array $dataToSubmit, array $expectedData): void
    {
        $form = $this->factory->create(TextFilterType::class);
        $form->submit($dataToSubmit);

        $this->assertSame($dataToSubmit, $form->getViewData());
        $this->assertSame($expectedData, $form->getData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
        $this->assertInstanceOf(TextFilterType::class, $form->getConfig()->getType()->getInnerType());
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $dataToSubmit
     */
    #[DataProvider('malformedData')]
    public function testMalformedDataMakesTheFilterInvalid(array $options, array $dataToSubmit): void
    {
        $form = $this->factory->create(TextFilterType::class, null, $options);
        $form->submit($dataToSubmit);

        $this->assertFalse($form->isSynchronized());
        $this->assertFalse($form->isValid());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function malformedData(): iterable
    {
        yield 'unknown comparison' => [[], ['comparison' => 'bogus', 'value' => 'abc']];
        yield 'array comparison' => [[], ['comparison' => ['like'], 'value' => 'abc']];
        yield 'array value' => [[], ['comparison' => ComparisonType::CONTAINS, 'value' => ['abc']]];
        yield 'array value with starts with' => [[], ['comparison' => ComparisonType::STARTS_WITH, 'value' => ['abc']]];
    }

    public static function submit(): iterable
    {
        yield [
            ['comparison' => ComparisonType::CONTAINS, 'value' => 'abc'],
            ['comparison' => 'like', 'value' => '%abc%'],
        ];
        yield [
            ['comparison' => ComparisonType::NOT_CONTAINS, 'value' => 'abc'],
            ['comparison' => 'not like', 'value' => '%abc%'],
        ];
        yield [
            ['comparison' => ComparisonType::STARTS_WITH, 'value' => 'abc'],
            ['comparison' => 'like', 'value' => 'abc%'],
        ];
        yield [
            ['comparison' => ComparisonType::ENDS_WITH, 'value' => 'abc'],
            ['comparison' => 'like', 'value' => '%abc'],
        ];
        yield [
            ['comparison' => ComparisonType::EQ, 'value' => 'abc'],
            ['comparison' => '=', 'value' => 'abc'],
        ];
        yield [
            ['comparison' => ComparisonType::NEQ, 'value' => 'abc'],
            ['comparison' => '!=', 'value' => 'abc'],
        ];
    }
}
