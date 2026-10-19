<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Maker;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Maker\CrudFieldGuesser;
use EasyCorp\Bundle\EasyAdminBundle\Maker\GuessedField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\MakerTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\MakerTestStatus;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Website;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Maker\Fixtures\ChildEntity;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CrudFieldGuesserTest extends KernelTestCase
{
    private CrudFieldGuesser $guesser;

    protected function setUp(): void
    {
        $this->guesser = new CrudFieldGuesser();
    }

    public function testFieldsOfDoctrineTypesAndPropertyNames(): void
    {
        $fields = $this->guesser->guess($this->getEntityMetadata(FieldTestEntity::class));

        $this->assertSame([
            'id' => "IdField::new('id')->hideOnForm()",
            'textField' => "TextField::new('textField')",
            'textareaField' => "TextareaField::new('textareaField')->hideOnIndex()",
            'emailField' => "EmailField::new('emailField')",
            'telephoneField' => "TelephoneField::new('telephoneField')",
            'urlField' => "UrlField::new('urlField')",
            'slugField' => "TextField::new('slugField')",
            'integerField' => "IntegerField::new('integerField')",
            'numberField' => "NumberField::new('numberField')",
            'dateField' => "DateField::new('dateField')",
            'timeField' => "TimeField::new('timeField')",
            'dateTimeField' => "DateTimeField::new('dateTimeField')",
            'booleanField' => "BooleanField::new('booleanField')",
            'arrayField' => "ArrayField::new('arrayField')->hideOnIndex()",
            'manyToOneAssociation' => "AssociationField::new('manyToOneAssociation')",
            'manyToManyAssociation' => "AssociationField::new('manyToManyAssociation')->hideOnIndex()",
        ], array_intersect_key($this->toPhp($fields), array_flip([
            'id', 'textField', 'textareaField', 'emailField', 'telephoneField', 'urlField', 'slugField', 'integerField',
            'numberField', 'dateField', 'timeField', 'dateTimeField', 'booleanField', 'arrayField',
            'manyToOneAssociation', 'manyToManyAssociation',
        ])));
    }

    public function testIndexColumnsAreLimited(): void
    {
        $fields = $this->guesser->guess($this->getEntityMetadata(FieldTestEntity::class));
        $fields = $this->guesser->limitIndexColumns(array_values($fields));

        $this->assertSame([
            "IdField::new('id')->hideOnForm()",
            "TextField::new('textField')",
            "TextareaField::new('textareaField')->hideOnIndex()",
            "TextareaField::new('textEditorField')->hideOnIndex()",
            "TextareaField::new('codeEditorField')->hideOnIndex()",
            "EmailField::new('emailField')",
            "TelephoneField::new('telephoneField')",
            "UrlField::new('urlField')",
            "TextField::new('slugField')",
            "IntegerField::new('integerField')",
            "NumberField::new('numberField')->hideOnIndex()",
            "IntegerField::new('moneyField')->hideOnIndex()",
        ], \array_slice($this->toPhp($fields), 0, 12));
    }

    public function testMakerTestEntity(): void
    {
        $fields = $this->guesser->guess($this->getEntityMetadata(MakerTestEntity::class));
        $fields = $this->guesser->limitIndexColumns(array_values($fields));

        $this->assertSame([
            "IdField::new('id')->hideOnForm()",
            "EmailField::new('email')",
            "TextareaField::new('description')->hideOnIndex()",
            "ChoiceField::new('status')",
            "TextField::new('publicId')->onlyOnDetail()",
            "DateTimeField::new('createdAt')->hideOnForm()",
            "DateTimeField::new('updatedAt')->onlyOnDetail()",
            "TextField::new('password')->onlyOnForms()",
            "AssociationField::new('relatedEntity')",
        ], $this->toPhp($fields));

        $notSuggestedFields = array_filter($fields, static fn (GuessedField $field): bool => !$field->suggested);
        $this->assertSame(['password'], array_column($notSuggestedFields, 'property'));
    }

    public function testEmbeddedAndAssociationFields(): void
    {
        $fields = $this->guesser->guess($this->getEntityMetadata(Project::class));

        $this->assertSame(
            ['id', 'name', 'price.amount', 'price.currency', 'latestRelease', 'leadDeveloper', 'projectIssues', 'favouriteProjectOf', 'projectTags', 'statesSimpleArray'],
            \array_slice(array_keys($fields), 0, 10)
        );
        $this->assertSame([
            'price.amount' => "IntegerField::new('price.amount')",
            'price.currency' => "TextField::new('price.currency')",
            'latestRelease' => "AssociationField::new('latestRelease')",
            'leadDeveloper' => "AssociationField::new('leadDeveloper')",
            'projectIssues' => "AssociationField::new('projectIssues')->hideOnIndex()",
        ], array_intersect_key($this->toPhp($fields), array_flip(['price.amount', 'price.currency', 'latestRelease', 'leadDeveloper', 'projectIssues'])));
    }

    public function testToManyAssociationWithOrphanRemoval(): void
    {
        $fields = $this->guesser->guess($this->getEntityMetadata(Website::class));

        $this->assertSame("AssociationField::new('pages')->hideOnIndex()", $fields['pages']->toPhp());
    }

    public function testPropertiesOfParentClassesGoFirst(): void
    {
        $metadata = new ClassMetadata(ChildEntity::class);
        $metadata->mapField(['fieldName' => 'notMappedToAnyProperty', 'type' => 'string']);
        $metadata->mapField(['fieldName' => 'title', 'type' => 'string']);
        $metadata->mapField(['fieldName' => 'name', 'type' => 'string']);
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);

        $this->assertSame(['id', 'name', 'title', 'notMappedToAnyProperty'], array_keys($this->guesser->guess($metadata)));
    }

    /**
     * @param class-string $expectedFieldFqcn
     */
    #[TestWith(['contactEmail', 'string', EmailField::class])]
    #[TestWith(['EMAIL', 'string', EmailField::class])]
    #[TestWith(['homepageUrl', 'string', UrlField::class])]
    #[TestWith(['companyWebsite', 'string', UrlField::class])]
    #[TestWith(['mobilePhone', 'string', TelephoneField::class])]
    #[TestWith(['title', 'string', TextField::class])]
    #[TestWith(['emailCount', 'integer', IntegerField::class])]
    public function testFieldsOfPropertyNames(string $propertyName, string $doctrineType, string $expectedFieldFqcn): void
    {
        $metadata = new ClassMetadata(MakerTestEntity::class);
        $metadata->mapField(['fieldName' => $propertyName, 'type' => $doctrineType]);

        $this->assertSame($expectedFieldFqcn, $this->guesser->guess($metadata)[$propertyName]->fieldFqcn);
    }

    #[TestWith(['simple_array'])]
    #[TestWith(['json'])]
    public function testListsOfEnumsAllowMultipleChoices(string $doctrineType): void
    {
        $metadata = new ClassMetadata(MakerTestEntity::class);
        $metadata->mapField(['fieldName' => 'statuses', 'type' => $doctrineType, 'enumType' => MakerTestStatus::class]);
        $field = $this->guesser->guess($metadata)['statuses'];

        $this->assertSame("ChoiceField::new('statuses')->allowMultipleChoices()", $field->toPhp());
        $this->assertSame('ChoiceField, multiple choices', $field->describe());
        $this->assertSame("ChoiceField::new('statuses')->allowMultipleChoices()->hideOnIndex()", $field->hideOnIndex()->toPhp());
    }

    public function testCompositeIdentifiersAreNotIdFields(): void
    {
        $metadata = new ClassMetadata(MakerTestEntity::class);
        $metadata->mapField(['fieldName' => 'orderId', 'type' => 'integer', 'id' => true]);
        $metadata->mapField(['fieldName' => 'productId', 'type' => 'integer', 'id' => true]);

        $this->assertSame(
            ['orderId' => "IntegerField::new('orderId')", 'productId' => "IntegerField::new('productId')"],
            $this->toPhp($this->guesser->guess($metadata))
        );
    }

    public function testPropertiesOfUnsupportedTypesAreLeftOut(): void
    {
        $metadata = new ClassMetadata(MakerTestEntity::class);
        $metadata->mapField(['fieldName' => 'title', 'type' => 'string']);
        $metadata->mapField(['fieldName' => 'location', 'type' => 'geometry_point']);

        $this->assertSame(['title'], array_keys($this->guesser->guess($metadata)));
    }

    /**
     * The guesser keeps its own map of Doctrine types to fields; this checks that
     * it doesn't drift from the map used to guess the fields at runtime.
     */
    public function testDoctrineTypesUseTheSameFieldsAsTheRuntimeGuessing(): void
    {
        /** @var array<string, class-string> $runtimeFieldFqcns */
        $runtimeFieldFqcns = (new \ReflectionProperty(FieldFactory::class, 'doctrineTypeToFieldFqcn'))->getValue();

        $metadata = new ClassMetadata(MakerTestEntity::class);
        foreach (array_keys($runtimeFieldFqcns) as $doctrineType) {
            $metadata->mapField(['fieldName' => 'property_'.$doctrineType, 'type' => $doctrineType]);
        }
        $fields = $this->guesser->guess($metadata);

        foreach ($runtimeFieldFqcns as $doctrineType => $runtimeFieldFqcn) {
            $this->assertArrayHasKey('property_'.$doctrineType, $fields, sprintf('The "%s" Doctrine type is not supported.', $doctrineType));
            $this->assertSame($runtimeFieldFqcn, $fields['property_'.$doctrineType]->fieldFqcn, sprintf('The field of the "%s" Doctrine type is different from the one used at runtime.', $doctrineType));
        }
    }

    /**
     * @param class-string $entityFqcn
     *
     * @return ClassMetadata<object>
     */
    private function getEntityMetadata(string $entityFqcn): ClassMetadata
    {
        return self::getContainer()->get('doctrine')->getManager()->getClassMetadata($entityFqcn);
    }

    /**
     * @param array<GuessedField> $fields
     *
     * @return array<string>
     */
    private function toPhp(array $fields): array
    {
        return array_map(static fn (GuessedField $field): string => $field->toPhp(), $fields);
    }
}
