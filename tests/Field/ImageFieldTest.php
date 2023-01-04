<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ImageConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use League\Flysystem\FilesystemOperator;

class ImageFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $projectDir = __DIR__.'/../TestApplication';
        $this->configurator = new ImageConfigurator($projectDir);
    }

    public function testFilesystemOperator(): void
    {
        $filesystemOperator = $this->createStub(FilesystemOperator::class);

        $field = ImageField::new('foo')->setFilesystemOperator($filesystemOperator);
        $fieldDto = $this->configure($field);

        self::assertNotNull($fieldDto->getCustomOption(ImageField::OPTION_FILESYSTEM_OPERATOR));
    }
}
