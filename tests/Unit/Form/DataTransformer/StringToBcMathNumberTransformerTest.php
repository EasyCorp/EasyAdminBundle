<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Form\DataTransformer;

use EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer\StringToBcMathNumberTransformer;
use PHPUnit\Framework\TestCase;

class StringToBcMathNumberTransformerTest extends TestCase
{
    private StringToBcMathNumberTransformer $transformer;

    protected function setUp(): void
    {
        if (\PHP_VERSION_ID < 80400) {
            $this->markTestSkipped('BcMath\Number requires PHP 8.4 or higher.');
        }

        $this->transformer = new StringToBcMathNumberTransformer();
    }

    public function testTransformNull(): void
    {
        self::assertNull($this->transformer->transform(null));
    }

    public function testTransformBcMathNumber(): void
    {
        $number = new \BcMath\Number('123.45');

        self::assertSame('123.45', $this->transformer->transform($number));
    }

    public function testTransformInvalidTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->transformer->transform(123.45);
    }

    public function testReverseTransformNull(): void
    {
        self::assertNull($this->transformer->reverseTransform(null));
    }

    public function testReverseTransformEmptyString(): void
    {
        self::assertNull($this->transformer->reverseTransform(''));
    }

    public function testReverseTransformString(): void
    {
        $result = $this->transformer->reverseTransform('123.45');

        self::assertInstanceOf(\BcMath\Number::class, $result);
        self::assertSame('123.45', (string) $result);
    }

    public function testReverseTransformInteger(): void
    {
        $result = $this->transformer->reverseTransform('42');

        self::assertInstanceOf(\BcMath\Number::class, $result);
        self::assertSame('42', (string) $result);
    }

    public function testReverseTransformNegativeNumber(): void
    {
        $result = $this->transformer->reverseTransform('-99.99');

        self::assertInstanceOf(\BcMath\Number::class, $result);
        self::assertSame('-99.99', (string) $result);
    }
}
