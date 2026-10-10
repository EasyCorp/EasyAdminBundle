<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Twig;

use EasyCorp\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\GlobalsInterface;

class EasyAdminTwigExtensionTest extends KernelTestCase
{
    /**
     * @dataProvider provideValuesForRepresentAsString
     */
    public function testRepresentAsString($value, $expectedValue, bool $assertRegex = false, string|callable|null $toStringMethod = null): void
    {
        $customTranslator = new class implements TranslatorInterface {
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return '*'.$id;
            }

            public function getLocale(): string
            {
                return 'en';
            }
        };

        $reflectedClass = new \ReflectionClass(EasyAdminTwigExtension::class);
        $twigExtensionInstance = $reflectedClass->newInstanceWithoutConstructor();
        $property = $reflectedClass->getProperty('translator');
        $property->setValue($twigExtensionInstance, $customTranslator);

        $result = $twigExtensionInstance->representAsString($value, $toStringMethod);

        if ($assertRegex) {
            $this->assertMatchesRegularExpression($expectedValue, $result);
        } else {
            $this->assertSame($expectedValue, $result);
        }

        $this->assertStringNotContainsString("\0", $result, 'The string representation of a value must not contain the null character (which can happen when the original value is an anonymous class object)');
    }

    public function testRepresentAsStringException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/The method "someMethod\(\)" does not exist or is not callable in the value of type "class@anonymous.*"/');

        $reflectedClass = new \ReflectionClass(EasyAdminTwigExtension::class);
        $twigExtensionInstance = $reflectedClass->newInstanceWithoutConstructor();

        $twigExtensionInstance->representAsString(new class {}, 'someMethod');
    }

    /**
     * @dataProvider provideValuesForFileSize
     */
    public function testFileSize(int $bytes, string $expected): void
    {
        $reflectedClass = new \ReflectionClass(EasyAdminTwigExtension::class);
        $twigExtensionInstance = $reflectedClass->newInstanceWithoutConstructor();

        $result = $twigExtensionInstance->fileSize($bytes);

        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider provideValuesForForceFileDownload
     */
    public function testForceFileDownload(string $filename, bool $expected): void
    {
        $reflectedClass = new \ReflectionClass(EasyAdminTwigExtension::class);
        $twigExtensionInstance = $reflectedClass->newInstanceWithoutConstructor();

        $this->assertSame($expected, $twigExtensionInstance->forceFileDownload($filename));
    }

    /**
     * @dataProvider provideValuesForIsNotEmpty
     */
    public function testIsNotEmpty(mixed $value, bool $expected): void
    {
        $isNotEmpty = $this->getTwigTestCallable('ea_is_not_empty');

        $this->assertSame($expected, $isNotEmpty($value));
    }

    public function testUid(): void
    {
        $uid = $this->getTwigFunctionCallable('ea_uid');

        // the generated IDs use the given prefix followed by a ULID (26 chars, Crockford base32)
        $this->assertMatchesRegularExpression('/^ea-[0-9A-HJKMNP-TV-Z]{26}$/', $uid());
        $this->assertMatchesRegularExpression('/^ea-form-[0-9A-HJKMNP-TV-Z]{26}$/', $uid('ea-form-'));
        $this->assertNotSame($uid(), $uid());
    }

    public function testDoesNotDefineTwigGlobals(): void
    {
        // an 'ea' global would replace the local 'ea' variable when Symfony resolves
        // the parent templates of form themes, because that only uses the Twig globals
        $this->assertFalse(is_subclass_of(EasyAdminTwigExtension::class, GlobalsInterface::class));
    }

    public static function provideValuesForIsNotEmpty(): iterable
    {
        yield [false, false];
        yield [null, false];
        yield ['', false];

        yield ['foo', true];
        yield [' ', true];
        yield [0, true];
        yield ['0', true];
        yield [[], true];
        // TranslatableInterface values must not be stringified (that would trigger
        // a deprecation for TranslatableMessage); any object counts as not empty
        yield [new class implements TranslatableInterface {
            public function trans(TranslatorInterface $translator, ?string $locale = null): string
            {
                return 'some value';
            }
        }, true];
    }

    private function getTwigFunctionCallable(string $name): callable
    {
        $reflectedClass = new \ReflectionClass(EasyAdminTwigExtension::class);
        $twigExtensionInstance = $reflectedClass->newInstanceWithoutConstructor();

        foreach ($twigExtensionInstance->getFunctions() as $function) {
            if ($name === $function->getName()) {
                return $function->getCallable();
            }
        }

        throw new \InvalidArgumentException(sprintf('The "%s" Twig function is not defined in %s.', $name, EasyAdminTwigExtension::class));
    }

    private function getTwigTestCallable(string $name): callable
    {
        $reflectedClass = new \ReflectionClass(EasyAdminTwigExtension::class);
        $twigExtensionInstance = $reflectedClass->newInstanceWithoutConstructor();

        foreach ($twigExtensionInstance->getTests() as $test) {
            if ($name === $test->getName()) {
                return $test->getCallable();
            }
        }

        throw new \InvalidArgumentException(sprintf('The "%s" Twig test is not defined in %s.', $name, EasyAdminTwigExtension::class));
    }

    public static function provideValuesForForceFileDownload(): iterable
    {
        // files the browser renders inline and can execute scripts from
        yield ['document.html', true];
        yield ['document.htm', true];
        yield ['document.xhtml', true];
        yield ['document.shtml', true];
        yield ['document.mhtml', true];
        yield ['image.svg', true];
        yield ['image.svgz', true];
        yield ['data.xml', true];
        yield ['transform.xsl', true];
        yield ['transform.xslt', true];

        // the check is case-insensitive
        yield ['DOCUMENT.HTML', true];
        yield ['IMAGE.SVG', true];

        // the check uses the (last) extension, regardless of the path
        yield ['uploads/files/payload.html', true];
        yield ['uploads/images/avatar.png', false];
        yield ['archive.tar.svg', true];
        yield ['archive.svg.zip', false];

        // safe types keep opening inline
        yield ['document.pdf', false];
        yield ['photo.jpg', false];
        yield ['photo.jpeg', false];
        yield ['photo.png', false];
        yield ['photo.gif', false];
        yield ['photo.webp', false];
        yield ['report.docx', false];
        yield ['notes.txt', false];

        // files without an extension are not forced to download
        yield ['README', false];
        yield ['', false];
    }

    public static function provideValuesForFileSize(): iterable
    {
        yield [0, '0 B'];
        yield [1, '1 B'];
        yield [1023, '1023 B'];
        yield [1024, '1 KB'];
        yield [999_900, '976.5 KB'];
        yield [1024 ** 2 - 100, '1023.9 KB'];
        yield [1024 ** 2, '1 MB'];
        yield [1024 ** 2 + 100, '1 MB'];
        yield [1024 ** 3 - 1, '1024 MB'];
        yield [1024 ** 3, '1 GB'];
        yield [1024 ** 3 + 1, '1 GB'];
        yield [1024 ** 4, '1 TB'];
        yield [1024 ** 5, '1 PB'];
        yield [1024 ** 6, '1 EB'];
        yield [\PHP_INT_MAX, '8 EB'];
    }

    public static function provideValuesForRepresentAsString(): iterable
    {
        yield [null, ''];
        yield ['foo bar', 'foo bar'];
        yield [5, '5'];
        yield [3.14, '3.14'];
        yield [true, 'true'];
        yield [false, 'false'];
        yield [[1, 2, 3], 'Array (3 items)'];
        yield [new class implements TranslatableInterface {
            public function trans(TranslatorInterface $translator, ?string $locale = null): string
            {
                return $translator->trans('some value');
            }
        }, '*some value'];
        yield [new class {}, '/class@anonymous.*/', true];
        yield [new class implements \Stringable {
            public function __toString()
            {
                return 'foo bar';
            }
        }, 'foo bar'];
        // a failing __toString() must not break the page rendering; e.g. the new entities
        // created for the collection form prototype may have uninitialized properties
        yield [new class implements \Stringable {
            private \DateTimeImmutable $startDate;

            public function __toString(): string
            {
                return $this->startDate->format('Y-m-d');
            }
        }, ''];
        yield [new class implements \Stringable {
            public function __toString(): string
            {
                throw new \RuntimeException('This object cannot be represented as a string');
            }
        }, ''];
        yield [new class {
            public function getId(): int
            {
                return 1234;
            }
        }, '/class@anonymous.* #1234/', true];

        yield ['foo', 'foo bar', false, static fn ($value) => $value.' bar'];
        yield [new class {
            public function someMethod(): string
            {
                return 'foo';
            }
        }, 'foo', false, 'someMethod'];
        yield ['foo', '*foo bar', false, static fn ($value, $translator) => $translator->trans($value.' bar')];
    }
}
