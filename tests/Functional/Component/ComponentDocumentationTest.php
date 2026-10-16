<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component;

use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Button;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Flag;
use EasyCorp\Bundle\EasyAdminBundle\UiKit\ComponentDocumentationReader;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Finder\Finder;

/**
 * Every prop and block of the components must be documented, so the
 * UI Kit and the IDEs can show what each of them does.
 */
class ComponentDocumentationTest extends KernelTestCase
{
    private const COMPONENTS_DIR = __DIR__.'/../../../templates/components';
    private const COMPONENT_CLASSES_DIR = __DIR__.'/../../../src/Twig/Component';

    #[DataProvider('provideComponentTemplates')]
    public function testEveryPropAndBlockIsDocumented(string $templateName): void
    {
        $this->skipIfDocumentationCommentsAreNotSupported();

        $reader = new ComponentDocumentationReader(static::getContainer()->get('twig'));
        $documentation = $reader->read($templateName);
        $this->assertNotNull($documentation);

        foreach ($documentation['props'] as $prop) {
            $this->assertNotNull($prop['type'], sprintf('The "%s" prop of "%s" has no "## <type> <description>" comment above it.', $prop['name'], $templateName));
            $this->assertNotNull($prop['description'], sprintf('The "%s" prop of "%s" has a type but no description.', $prop['name'], $templateName));
        }

        foreach ($documentation['blocks'] as $block) {
            $this->assertNotNull($block['description'], sprintf('The "%s" block of "%s" has no "{## ... ##}" comment above it.', $block['name'], $templateName));
        }
    }

    /**
     * @param class-string $componentClass
     */
    #[DataProvider('provideComponentClasses')]
    public function testEveryPropOfClassBackedComponentsIsDocumented(string $componentClass): void
    {
        $reader = new ComponentDocumentationReader(static::getContainer()->get('twig'));
        $props = $reader->readClassProps($componentClass);

        $this->assertNotEmpty($props);
        foreach ($props as $prop) {
            $this->assertNotNull($prop['description'], sprintf('The "%s" prop of the "%s" component has no PHP docblock.', $prop['name'], $componentClass));
        }
    }

    public function testReaderReturnsTheTypeDefaultValueAndDescriptionOfClassProps(): void
    {
        $reader = new ComponentDocumentationReader(static::getContainer()->get('twig'));

        $buttonProps = array_column($reader->readClassProps(Button::class), null, 'name');
        // the type comes from mount() (which also accepts strings) and the default from the property
        $this->assertSame(['ButtonVariant|string|null', "'default'", false], [$buttonProps['variant']['type'], $buttonProps['variant']['default'], $buttonProps['variant']['required']]);
        $this->assertSame(['string|null', "'POST'"], [$buttonProps['method']['type'], $buttonProps['method']['default']]);
        $this->assertSame(['bool', 'false'], [$buttonProps['isBlock']['type'], $buttonProps['isBlock']['default']]);
        $this->assertSame('Whether the button takes the full width of its container.', $buttonProps['isBlock']['description']);
        $this->assertArrayNotHasKey('translator', $buttonProps);

        $flagProps = array_column($reader->readClassProps(Flag::class), null, 'name');
        $this->assertSame(['string', null, true], [$flagProps['countryCode']['type'], $flagProps['countryCode']['default'], $flagProps['countryCode']['required']]);
        $this->assertSame('17', $flagProps['height']['default']);
    }

    public function testReaderReturnsTheTypeDefaultValueAndDescriptionOfEachProp(): void
    {
        $this->skipIfDocumentationCommentsAreNotSupported();

        $reader = new ComponentDocumentationReader(static::getContainer()->get('twig'));
        $documentation = $reader->read('@EasyAdmin/components/Sidebar/Item.html.twig');
        $props = array_column($documentation['props'], null, 'name');

        $this->assertSame(['name' => 'label', 'type' => 'string', 'description' => 'The item text (pass it already translated; it may contain HTML).', 'default' => null, 'required' => true], $props['label']);
        $this->assertSame(['null', false], [$props['href']['default'], $props['href']['required']]);
        $this->assertSame('false', $props['active']['default']);
        $this->assertSame('{}', $props['htmlAttributes']['default']);
        $this->assertSame(['badge', 'submenu'], array_column($documentation['blocks'], 'name'));
        $this->assertSame("'1'", array_column($reader->read('@EasyAdmin/components/Switch.html.twig')['props'], 'default', 'name')['value']);
    }

    public static function provideComponentClasses(): iterable
    {
        foreach ((new Finder())->files()->in(self::COMPONENT_CLASSES_DIR)->depth(0)->name('*.php')->sortByName() as $file) {
            $componentClass = 'EasyCorp\\Bundle\\EasyAdminBundle\\Twig\\Component\\'.$file->getBasename('.php');

            yield $file->getBasename('.php') => [$componentClass];
        }
    }

    public static function provideComponentTemplates(): iterable
    {
        foreach ((new Finder())->files()->in(self::COMPONENTS_DIR)->name('*.html.twig')->sortByName() as $file) {
            $templateName = '@EasyAdmin/components/'.str_replace('\\', '/', $file->getRelativePathname());

            yield $templateName => [$templateName];
        }
    }

    private function skipIfDocumentationCommentsAreNotSupported(): void
    {
        if (!ComponentDocumentationReader::isSupported()) {
            $this->markTestSkipped('Documentation comments require Twig 3.29 and TwigComponent 3.5 or higher.');
        }
    }
}
