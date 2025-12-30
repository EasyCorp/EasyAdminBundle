<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field\Configurator;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ChoiceConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Field\AbstractFieldTest;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\ProjectDomain\Project;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Model\Priority;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Model\Status;

class ChoiceConfiguratorTest extends AbstractFieldTest
{
    private EntityDto $projectDto;

    protected function setUp(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->projectDto = new EntityDto(Project::class, $entityManager->getClassMetadata(Project::class));

        $this->configurator = new ChoiceConfigurator();
    }

    protected function getEntityDto(): EntityDto
    {
        return $this->projectDto;
    }

    /**
     * @dataProvider fieldFqcns
     */
    public function testSupports(string $fieldFqcn, bool $expectedIsSupported): void
    {
        $field = new FieldDto();
        $field->setFieldFqcn($fieldFqcn);

        $this->assertSame($expectedIsSupported, $this->configurator->supports($field, $this->projectDto));
    }

    public static function fieldFqcns(): iterable
    {
        yield [ChoiceField::class, true];
        yield [TextField::class, false];
        yield [IdField::class, false];
    }

    public function testBackedEnum(): void
    {
        $field = ChoiceField::new('status');

        $this->assertSame(
            [
                'Draft' => Status::Draft,
                'Published' => Status::Published,
                'Deleted' => Status::Deleted,
            ],
            $this->configure($field)->getFormTypeOption('choices'),
        );
    }

    public function testBackedEnumWithCustomOptions(): void
    {
        $field = ChoiceField::new('status')->setCustomOptions(['choices' => Status::cases()]);

        $this->assertSame(
            [
                'Draft' => Status::Draft,
                'Published' => Status::Published,
                'Deleted' => Status::Deleted,
            ],
            $this->configure($field)->getFormTypeOption('choices'),
        );
    }

    public function testBackedEnumLabels(): void
    {
        $field = ChoiceField::new('status')->setCustomOptions(['choices' => [
            'Draft label' => Status::Draft,
            'Published label' => Status::Published,
            'Deleted label' => Status::Deleted,
        ]]);

        $this->assertSame(
            [
                'Draft label' => Status::Draft,
                'Published label' => Status::Published,
                'Deleted label' => Status::Deleted,
            ],
            $this->configure($field)->getFormTypeOption('choices'),
        );
    }

    public function testUnitEnum(): void
    {
        $field = ChoiceField::new('priority');

        $this->assertSame(
            [
                'High' => Priority::High,
                'Normal' => Priority::Normal,
                'Low' => Priority::Low,
            ],
            $this->configure($field)->getFormTypeOption('choices'),
        );
    }

    public function testUnitEnumChoicesWithCustomOptions(): void
    {
        $field = ChoiceField::new('priority');
        $field->setCustomOptions(['choices' => Priority::cases()]);

        $this->assertSame(
            [
                'High' => Priority::High,
                'Normal' => Priority::Normal,
                'Low' => Priority::Low,
            ],
            $this->configure($field)->getFormTypeOption('choices'),
        );
    }
}
