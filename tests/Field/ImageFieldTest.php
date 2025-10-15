<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Adapter\FlysystemFileAdapter;
use EasyCorp\Bundle\EasyAdminBundle\Adapter\UploadedFileAdapterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ImageConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

class ImageFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $projectDir = __DIR__.'/../TestApplication';
        $this->configurator = new ImageConfigurator($projectDir);
    }

    public function testFlysystemFileAdapter(): void
    {
        /** @var UploadedFileAdapterInterface $filesystemOperator */
        $filesystemOperator = $this->createStub(FlysystemFileAdapter::class);

        $field = ImageField::new('foo')->setUploadedFileAdapter($filesystemOperator);
        $fieldDto = $this->configure($field);

        self::assertNotNull($fieldDto->getCustomOption(ImageField::OPTION_UPLOADED_FILE_ADAPTER));
    }
}
