<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Bill;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Customer;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\EntityFactory\Address;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Page;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Developer;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Money;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\ProjectRelease;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\ProjectReleaseCategory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\ActionTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\BatchActionTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\DefaultCrudTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldRelatedEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FilterRelatedEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FilterTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SearchTestAuthor;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SearchTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SortTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SortTestRelatedEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Website;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 30; ++$i) {
            $category = (new Category())
                ->setName('Category '.$i)
                ->setSlug('category-'.$i);

            $this->addReference('category'.$i, $category);
            $manager->persist($category);
        }

        for ($i = 0; $i < 5; ++$i) {
            $user = (new User())
                ->setName('User '.$i)
                ->setEmail('user'.$i.'@example.com');

            $this->addReference('user'.$i, $user);
            $manager->persist($user);
        }

        for ($i = 0; $i < 20; ++$i) {
            $blogPost = (new BlogPost())
                ->setTitle('Blog Post '.$i)
                ->setSlug('blog-post-'.$i)
                ->setContent('Lorem Ipsum Dolor Sit Amet.')
                ->setCreatedAt(new \DateTimeImmutable('2020-11-'.($i + 1).' 09:00:00'))
                ->setPublishedAt(new \DateTimeImmutable('2020-11-'.($i + 1).' 11:00:00'))
                ->addCategory($this->getReference('category'.($i % 10), Category::class))
                ->setAuthor($this->getReference('user'.($i % 5), User::class));

            if ($i < 10) {
                $blogPost->setPublisher(
                    $this->getReference('user'.(($i + 1) % 5), User::class)
                );
            }

            $manager->persist($blogPost);
        }

        $this->addAssociationFixtures($manager);

        $this->addFieldTestFixtures($manager);

        $this->addFilterTestFixtures($manager);

        $this->addBatchActionTestFixtures($manager);

        $this->addDefaultCrudTestFixtures($manager);

        $this->addSortTestFixtures($manager);

        $this->addSearchTestFixtures($manager);

        $this->addActionTestFixtures($manager);

        $this->addProjectDomainSearchFixtures($manager);

        $manager->flush();
    }

    private function addAssociationFixtures(ObjectManager $manager)
    {
        // customer <-Many-To-Many-> Bill

        // add 10 Bills
        for ($i = 0; $i < 10; ++$i) {
            $bill = (new Bill())
                ->setName('Bill '.$i);

            $this->addReference('bill'.$i, $bill);
            $manager->persist($bill);
        }

        // pregenerated random amount of elements for each Bill
        $manyToManyAmount = [3, 0, 0, 1, 4, 0, 2, 2, 1, 1];

        // amount of elements is the sum of the manyToManyAmount array (Generation was implemented in a way that does not include duplicates)
        $manyToManyMapping = [6, 5, 6, 3, 1, 7, 2, 9, 4, 6, 2, 6, 1, 4, 0, 2, 4, 1, 6, 0, 9, 6, 7, 9, 8, 5, 2, 4, 9, 4, 0, 8, 7, 1, 3, 2, 1, 8, 4, 9, 7, 5, 3, 0, 6];
        $manyToManyIndex = 0;

        // add 10 Customer
        for ($i = 0; $i < 10; ++$i) {
            $customer = (new Customer())
                ->setName('Customer '.$i);

            $amount = $manyToManyAmount[$i];

            if ($amount > 0) {
                for ($j = 0; $j < $amount; ++$j) {
                    $customer->addBill(
                        $this->getReference('bill'.$manyToManyMapping[$manyToManyIndex++], Bill::class)
                    );
                }
            }

            $this->addReference('customer'.$i, $customer);
            $manager->persist($customer);
        }

        // page <-Many-To-One-> Website

        // add 10 Page
        for ($i = 0; $i < 10; ++$i) {
            $page = (new Page())
                ->setName('Page '.$i);

            $this->addReference('page'.$i, $page);
            $manager->persist($page);
        }

        // pregenerated random amount of elements for each Website
        $oneToManyAmount = [4, 1, 2, 5, 4, 4, 0, 4, 4, 3];

        // amount of elements is the sum of the oneToManyAmount array (Generation was implemented in a way that does not include duplicates)
        $oneToManyMapping = [3, 8, 0, 7, 4, 0, 5, 2, 0, 1, 0, 8, 2, 4, 3, 3, 2, 5, 4, 7, 9, 2, 0, 1, 9, 6, 8, 5, 2, 9, 5, 0, 6, 1, 3, 8, 6, 9, 2, 0, 4, 8, 3, 7, 1];
        $oneToManyIndex = 0;

        // add 10 Website
        for ($i = 0; $i < 10; ++$i) {
            $website = (new Website())
                ->setName('Website '.$i);

            $amount = $oneToManyAmount[$i];

            if ($amount > 0) {
                for ($j = 0; $j < $amount; ++$j) {
                    $website->addPage(
                        $this->getReference('page'.$oneToManyMapping[$oneToManyIndex++], Page::class)
                    );
                }
            }

            $this->addReference('website'.$i, $website);
            $manager->persist($website);
        }
    }

    private function addFieldTestFixtures(ObjectManager $manager): void
    {
        // create related entities for association fields
        $fieldRelatedEntities = [];
        for ($i = 1; $i <= 3; ++$i) {
            $related = new FieldRelatedEntity();
            $related->setName('Field Related '.$i);
            $manager->persist($related);
            $fieldRelatedEntities[$i] = $related;
            $this->addReference('fieldRelated'.$i, $related);
        }

        // create several FieldTestEntity entries with different values for testing fields
        $testData = [
            [
                'textField' => 'Hello World',
                'textareaField' => "This is a multi-line\ntext area field\nwith several lines.",
                'textEditorField' => '<p>This is <strong>rich text</strong> content.</p>',
                'codeEditorField' => "<?php\necho 'Hello World';",
                'emailField' => 'test@example.com',
                'telephoneField' => '+1-555-123-4567',
                'urlField' => 'https://symfony.com',
                'slugField' => 'hello-world',
                'integerField' => 42,
                'numberField' => 3.14159,
                'moneyField' => 9999, // stored as cents
                'percentField' => 0.75,
                'dateField' => new \DateTime('2024-03-15'),
                'timeField' => new \DateTime('14:30:00'),
                'dateTimeField' => new \DateTime('2024-03-15 14:30:00'),
                'booleanField' => true,
                'choiceField' => 'option_a',
                'multipleChoiceField' => ['choice1', 'choice2'],
                'arrayField' => ['item1', 'item2', 'item3'],
                'countryField' => 'US',
                'languageField' => 'en',
                'localeField' => 'en_US',
                'timezoneField' => 'America/New_York',
                'currencyField' => 'USD',
                'imageField' => 'uploads/test-image.jpg',
                'avatarField' => 'uploads/avatar.png',
                'colorField' => '#FF5733',
                'hiddenField' => 'hidden_value_1',
                'manyToOneAssociationIndex' => 1,
                'manyToManyAssociationIndices' => [1, 2],
            ],
            [
                'textField' => 'Another Text',
                'textareaField' => 'Short text area content.',
                'textEditorField' => '<p>Simple paragraph.</p>',
                'codeEditorField' => 'console.log("test");',
                'emailField' => 'admin@example.org',
                'telephoneField' => '+44-20-7946-0958',
                'urlField' => 'https://easyadmin.io',
                'slugField' => 'another-text',
                'integerField' => 100,
                'numberField' => 2.71828,
                'moneyField' => 15000,
                'percentField' => 0.25,
                'dateField' => new \DateTime('2023-12-25'),
                'timeField' => new \DateTime('09:00:00'),
                'dateTimeField' => new \DateTime('2023-12-25 09:00:00'),
                'booleanField' => false,
                'choiceField' => 'option_b',
                'multipleChoiceField' => ['choice3'],
                'arrayField' => ['single'],
                'countryField' => 'GB',
                'languageField' => 'fr',
                'localeField' => 'fr_FR',
                'timezoneField' => 'Europe/London',
                'currencyField' => 'EUR',
                'imageField' => null,
                'avatarField' => null,
                'colorField' => '#00FF00',
                'hiddenField' => 'hidden_value_2',
                'manyToOneAssociationIndex' => 2,
                'manyToManyAssociationIndices' => [3],
            ],
            [
                'textField' => 'Third Entry',
                'textareaField' => null,
                'textEditorField' => null,
                'codeEditorField' => null,
                'emailField' => null,
                'telephoneField' => null,
                'urlField' => null,
                'slugField' => 'third-entry',
                'integerField' => 0,
                'numberField' => 0.0,
                'moneyField' => 0,
                'percentField' => 0.0,
                'dateField' => null,
                'timeField' => null,
                'dateTimeField' => null,
                'booleanField' => null,
                'choiceField' => null,
                'multipleChoiceField' => [],
                'arrayField' => [],
                'countryField' => null,
                'languageField' => null,
                'localeField' => null,
                'timezoneField' => null,
                'currencyField' => null,
                'imageField' => null,
                'avatarField' => null,
                'colorField' => null,
                'hiddenField' => null,
                'manyToOneAssociationIndex' => null,
                'manyToManyAssociationIndices' => [],
            ],
        ];

        foreach ($testData as $index => $data) {
            $entity = new FieldTestEntity();
            $entity->setTextField($data['textField']);
            $entity->setTextareaField($data['textareaField']);
            $entity->setTextEditorField($data['textEditorField']);
            $entity->setCodeEditorField($data['codeEditorField']);
            $entity->setEmailField($data['emailField']);
            $entity->setTelephoneField($data['telephoneField']);
            $entity->setUrlField($data['urlField']);
            $entity->setSlugField($data['slugField']);
            $entity->setIntegerField($data['integerField']);
            $entity->setNumberField($data['numberField']);
            $entity->setMoneyField($data['moneyField']);
            $entity->setPercentField($data['percentField']);
            $entity->setDateField($data['dateField']);
            $entity->setTimeField($data['timeField']);
            $entity->setDateTimeField($data['dateTimeField']);
            $entity->setBooleanField($data['booleanField']);
            $entity->setChoiceField($data['choiceField']);
            $entity->setMultipleChoiceField($data['multipleChoiceField']);
            $entity->setArrayField($data['arrayField']);
            $entity->setCountryField($data['countryField']);
            $entity->setLanguageField($data['languageField']);
            $entity->setLocaleField($data['localeField']);
            $entity->setTimezoneField($data['timezoneField']);
            $entity->setCurrencyField($data['currencyField']);
            $entity->setImageField($data['imageField']);
            $entity->setAvatarField($data['avatarField']);
            $entity->setColorField($data['colorField']);
            $entity->setHiddenField($data['hiddenField']);

            // set association fields
            if (null !== $data['manyToOneAssociationIndex']) {
                $entity->setManyToOneAssociation($fieldRelatedEntities[$data['manyToOneAssociationIndex']]);
            }
            foreach ($data['manyToManyAssociationIndices'] as $relatedIndex) {
                $entity->addManyToManyAssociation($fieldRelatedEntities[$relatedIndex]);
            }

            $this->addReference('fieldTest'.$index, $entity);
            $manager->persist($entity);
        }
    }

    private function addFilterTestFixtures(ObjectManager $manager): void
    {
        // create related entities first
        $relatedEntities = [];
        for ($i = 1; $i <= 3; ++$i) {
            $related = new FilterRelatedEntity();
            $related->setName('Related Entity '.$i);
            $manager->persist($related);
            $relatedEntities[$i] = $related;
            $this->addReference('filterRelated'.$i, $related);
        }

        // define test data with various filter-testable values
        $testData = [
            // iD 1: Entity with all fields populated - text contains "alpha"
            [
                'textFilter' => 'alpha test string',
                'textareaFilter' => 'This is alpha content in textarea',
                'numericFilter' => 100,
                'decimalFilter' => 10.5,
                'dateFilter' => new \DateTime('2024-01-15'),
                'dateTimeFilter' => new \DateTime('2024-01-15 10:30:00'),
                'booleanFilter' => true,
                'choiceFilter' => 'option_a',
                'arrayFilter' => ['tag1', 'tag2'],
                'nullFilter' => 'not null value',
                'relatedEntity' => $relatedEntities[1],
                'comparisonFilter' => 10,
            ],
            // iD 2: Entity with text containing "beta", different numeric
            [
                'textFilter' => 'beta sample text',
                'textareaFilter' => 'Beta content here',
                'numericFilter' => 200,
                'decimalFilter' => 25.75,
                'dateFilter' => new \DateTime('2024-02-20'),
                'dateTimeFilter' => new \DateTime('2024-02-20 14:00:00'),
                'booleanFilter' => false,
                'choiceFilter' => 'option_b',
                'arrayFilter' => ['tag2', 'tag3'],
                'nullFilter' => 'another value',
                'relatedEntity' => $relatedEntities[2],
                'comparisonFilter' => 20,
            ],
            // iD 3: Entity with text containing "gamma", null values for testing NullFilter
            [
                'textFilter' => 'gamma record',
                'textareaFilter' => null,
                'numericFilter' => 50,
                'decimalFilter' => 5.25,
                'dateFilter' => new \DateTime('2024-03-10'),
                'dateTimeFilter' => new \DateTime('2024-03-10 08:15:00'),
                'booleanFilter' => true,
                'choiceFilter' => 'option_c',
                'arrayFilter' => ['tag1'],
                'nullFilter' => null, // For NullFilter testing
                'relatedEntity' => $relatedEntities[1],
                'comparisonFilter' => 30,
            ],
            // iD 4: Entity with text starting with "alpha"
            [
                'textFilter' => 'alphabetical order',
                'textareaFilter' => 'Textarea with specific content',
                'numericFilter' => 150,
                'decimalFilter' => 15.0,
                'dateFilter' => new \DateTime('2024-01-25'),
                'dateTimeFilter' => new \DateTime('2024-01-25 16:45:00'),
                'booleanFilter' => false,
                'choiceFilter' => 'option_a',
                'arrayFilter' => ['tag3'],
                'nullFilter' => null,
                'relatedEntity' => $relatedEntities[3],
                'comparisonFilter' => 40,
            ],
            // iD 5: Entity with numeric in specific range for between tests
            [
                'textFilter' => 'delta entry',
                'textareaFilter' => 'Delta textarea content',
                'numericFilter' => 175,
                'decimalFilter' => 17.5,
                'dateFilter' => new \DateTime('2024-02-01'),
                'dateTimeFilter' => new \DateTime('2024-02-01 12:00:00'),
                'booleanFilter' => true,
                'choiceFilter' => 'option_b',
                'arrayFilter' => ['tag1', 'tag2', 'tag3'],
                'nullFilter' => 'has value',
                'relatedEntity' => $relatedEntities[2],
                'comparisonFilter' => 50,
            ],
            // iD 6: Entity ending with "test"
            [
                'textFilter' => 'this ends with test',
                'textareaFilter' => 'Content ending in test',
                'numericFilter' => 300,
                'decimalFilter' => 30.0,
                'dateFilter' => new \DateTime('2024-04-01'),
                'dateTimeFilter' => new \DateTime('2024-04-01 09:00:00'),
                'booleanFilter' => null, // null boolean for testing
                'choiceFilter' => 'option_c',
                'arrayFilter' => [],
                'nullFilter' => 'value exists',
                'relatedEntity' => null, // null relation for testing
                'comparisonFilter' => 60,
            ],
        ];

        foreach ($testData as $index => $data) {
            $entity = new FilterTestEntity();
            $entity->setTextFilter($data['textFilter']);
            $entity->setTextareaFilter($data['textareaFilter']);
            $entity->setNumericFilter($data['numericFilter']);
            $entity->setDecimalFilter($data['decimalFilter']);
            $entity->setDateFilter($data['dateFilter']);
            $entity->setDateTimeFilter($data['dateTimeFilter']);
            $entity->setBooleanFilter($data['booleanFilter']);
            $entity->setChoiceFilter($data['choiceFilter']);
            $entity->setArrayFilter($data['arrayFilter']);
            $entity->setNullFilter($data['nullFilter']);
            $entity->setRelatedEntity($data['relatedEntity']);
            $entity->setComparisonFilter($data['comparisonFilter']);

            $this->addReference('filterTest'.($index + 1), $entity);
            $manager->persist($entity);
        }
    }

    private function addBatchActionTestFixtures(ObjectManager $manager): void
    {
        // create entities for batch action testing
        // we need enough entities to test batch operations
        $testData = [
            ['name' => 'Batch Item 1', 'active' => true, 'status' => 'pending'],
            ['name' => 'Batch Item 2', 'active' => true, 'status' => 'pending'],
            ['name' => 'Batch Item 3', 'active' => false, 'status' => 'inactive'],
            ['name' => 'Batch Item 4', 'active' => true, 'status' => 'pending'],
            ['name' => 'Batch Item 5', 'active' => false, 'status' => 'inactive'],
            ['name' => 'Batch Item 6', 'active' => true, 'status' => 'active'],
            ['name' => 'Batch Item 7', 'active' => false, 'status' => 'inactive'],
            ['name' => 'Batch Item 8', 'active' => true, 'status' => 'active'],
            ['name' => 'Batch Item 9', 'active' => true, 'status' => 'pending'],
            ['name' => 'Batch Item 10', 'active' => false, 'status' => 'inactive'],
        ];

        foreach ($testData as $index => $data) {
            $entity = new BatchActionTestEntity();
            $entity->setName($data['name']);
            $entity->setActive($data['active']);
            $entity->setStatus($data['status']);

            $this->addReference('batchActionTest'.($index + 1), $entity);
            $manager->persist($entity);
        }
    }

    private function addDefaultCrudTestFixtures(ObjectManager $manager): void
    {
        // create entities for testing default CRUD operations
        // we need a moderate number of entities to test pagination and basic operations
        $testData = [
            ['name' => 'CRUD Test Item 1', 'description' => 'First test item for CRUD operations', 'active' => true, 'priority' => 10],
            ['name' => 'CRUD Test Item 2', 'description' => 'Second test item for CRUD operations', 'active' => true, 'priority' => 20],
            ['name' => 'CRUD Test Item 3', 'description' => 'Third test item for CRUD operations', 'active' => false, 'priority' => 30],
            ['name' => 'CRUD Test Item 4', 'description' => null, 'active' => true, 'priority' => null],
            ['name' => 'CRUD Test Item 5', 'description' => 'Fifth test item for CRUD operations', 'active' => false, 'priority' => 50],
            ['name' => 'CRUD Test Item 6', 'description' => 'Sixth test item for CRUD operations', 'active' => true, 'priority' => 60],
            ['name' => 'CRUD Test Item 7', 'description' => 'Seventh test item for CRUD operations', 'active' => true, 'priority' => 70],
            ['name' => 'CRUD Test Item 8', 'description' => 'Eighth test item for CRUD operations', 'active' => false, 'priority' => 80],
            ['name' => 'CRUD Test Item 9', 'description' => 'Ninth test item for CRUD operations', 'active' => true, 'priority' => 90],
            ['name' => 'CRUD Test Item 10', 'description' => 'Tenth test item for CRUD operations', 'active' => true, 'priority' => 100],
        ];

        foreach ($testData as $index => $data) {
            $entity = new DefaultCrudTestEntity();
            $entity->setName($data['name']);
            $entity->setDescription($data['description']);
            $entity->setActive($data['active']);
            $entity->setPriority($data['priority']);

            $this->addReference('defaultCrudTest'.($index + 1), $entity);
            $manager->persist($entity);
        }
    }

    private function addSortTestFixtures(ObjectManager $manager): void
    {
        // create related entities first (for ManyToOne and ManyToMany relations)
        $relatedEntities = [];
        $relatedNames = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon'];
        foreach ($relatedNames as $index => $name) {
            $related = new SortTestRelatedEntity();
            $related->setName($name);
            $manager->persist($related);
            $relatedEntities[$index] = $related;
            $this->addReference('sortRelated'.$index, $related);
        }

        // create main entities with varying relationship counts
        $testData = [
            // entity 1: Many relations for testing count sorting
            [
                'textField' => 'Zebra Entity',
                'integerField' => 100,
                'dateTimeField' => new \DateTime('2024-01-15 10:00:00'),
                'manyToOneIndex' => 0, // Alpha
                'oneToManyCount' => 4,
                'manyToManyIndices' => [0, 1, 2, 3, 4],
            ],
            // entity 2: Few relations
            [
                'textField' => 'Apple Entity',
                'integerField' => 50,
                'dateTimeField' => new \DateTime('2024-03-20 14:30:00'),
                'manyToOneIndex' => 1, // Beta
                'oneToManyCount' => 1,
                'manyToManyIndices' => [0],
            ],
            // entity 3: No relations
            [
                'textField' => 'Mango Entity',
                'integerField' => 75,
                'dateTimeField' => new \DateTime('2024-02-10 08:15:00'),
                'manyToOneIndex' => null,
                'oneToManyCount' => 0,
                'manyToManyIndices' => [],
            ],
            // entity 4: Medium relations
            [
                'textField' => 'Banana Entity',
                'integerField' => 200,
                'dateTimeField' => new \DateTime('2024-04-05 16:45:00'),
                'manyToOneIndex' => 2, // Gamma
                'oneToManyCount' => 2,
                'manyToManyIndices' => [1, 2],
            ],
            // entity 5: Different numeric values for testing
            [
                'textField' => 'Cherry Entity',
                'integerField' => 25,
                'dateTimeField' => new \DateTime('2024-01-01 00:00:00'),
                'manyToOneIndex' => 4, // Epsilon
                'oneToManyCount' => 3,
                'manyToManyIndices' => [2, 3, 4],
            ],
            // entity 6: Null datetime for testing null handling
            [
                'textField' => 'Orange Entity',
                'integerField' => 150,
                'dateTimeField' => null,
                'manyToOneIndex' => 3, // Delta
                'oneToManyCount' => 0,
                'manyToManyIndices' => [0, 4],
            ],
        ];

        foreach ($testData as $index => $data) {
            $entity = new SortTestEntity();
            $entity->setTextField($data['textField']);
            $entity->setIntegerField($data['integerField']);
            $entity->setDateTimeField($data['dateTimeField']);

            // set ManyToOne relation
            if (null !== $data['manyToOneIndex']) {
                $entity->setManyToOneRelation($relatedEntities[$data['manyToOneIndex']]);
            }

            // add ManyToMany relations
            foreach ($data['manyToManyIndices'] as $relatedIndex) {
                $entity->addManyToManyRelation($relatedEntities[$relatedIndex]);
            }

            $manager->persist($entity);
            $this->addReference('sortTest'.$index, $entity);

            // create OneToMany related entities pointing back to this entity
            for ($i = 0; $i < $data['oneToManyCount']; ++$i) {
                $oneToManyRelated = new SortTestRelatedEntity();
                $oneToManyRelated->setName(sprintf('Related to %s #%d', $data['textField'], $i + 1));
                $oneToManyRelated->setSortTestEntity($entity);
                $manager->persist($oneToManyRelated);
            }
        }
    }

    private function addSearchTestFixtures(ObjectManager $manager): void
    {
        // create authors first
        $authors = [];
        $authorData = [
            ['name' => 'John Smith', 'email' => 'john.smith@example.com', 'addressStreet' => 10, 'addressCity' => 'New York'],
            ['name' => 'Jane Doe', 'email' => 'jane.doe@example.com', 'addressStreet' => 20, 'addressCity' => 'London'],
            ['name' => 'Alice Johnson', 'email' => 'alice@company.org', 'addressStreet' => 30, 'addressCity' => 'New York'],
            ['name' => 'Bob Williams', 'email' => 'bob.williams@test.net', 'addressStreet' => 40, 'addressCity' => 'Paris'],
        ];

        foreach ($authorData as $index => $data) {
            $author = new SearchTestAuthor();
            $author->setName($data['name']);
            $author->setEmail($data['email']);
            $author->setAddress((new Address())->setStreet($data['addressStreet'])->setCity($data['addressCity']));
            $manager->persist($author);
            $authors[$index] = $author;
            $this->addReference('searchAuthor'.$index, $author);
        }

        // create search test entities with various searchable content
        $testData = [
            [
                'searchableTextField' => 'Introduction to PHP Programming',
                'searchableContentField' => 'PHP is a popular general-purpose scripting language that is especially suited to web development. It was created by Rasmus Lerdorf in 1994.',
                'nonSearchableField' => 'secret-code-123',
                'authorIndex' => 0,
            ],
            [
                'searchableTextField' => 'Advanced Symfony Framework Guide',
                'searchableContentField' => 'Symfony is a set of reusable PHP components and a PHP framework for web projects. This guide covers advanced topics like dependency injection and event dispatching.',
                'nonSearchableField' => 'hidden-data-456',
                'authorIndex' => 1,
            ],
            [
                'searchableTextField' => 'Database Design Patterns',
                'searchableContentField' => 'Learn about various database design patterns including repository pattern, unit of work, and query objects. These patterns help organize database access code.',
                'nonSearchableField' => 'private-info-789',
                'authorIndex' => 2,
            ],
            [
                'searchableTextField' => 'JavaScript and PHP Integration',
                'searchableContentField' => 'Modern web applications often combine JavaScript frontend with PHP backend. AJAX requests allow seamless communication between client and server.',
                'nonSearchableField' => 'confidential-abc',
                'authorIndex' => 0,
            ],
            [
                'searchableTextField' => 'Testing Best Practices',
                'searchableContentField' => 'Unit testing, integration testing, and functional testing are essential for maintaining code quality. PHPUnit is the standard testing framework for PHP projects.',
                'nonSearchableField' => 'restricted-def',
                'authorIndex' => 3,
            ],
            [
                'searchableTextField' => 'REST API Development',
                'searchableContentField' => 'RESTful APIs follow specific architectural constraints. This article covers HTTP methods, status codes, and best practices for API design.',
                'nonSearchableField' => null,
                'authorIndex' => 1,
            ],
            [
                'searchableTextField' => 'Doctrine ORM Mastery',
                'searchableContentField' => 'Doctrine ORM provides a powerful data-mapper pattern implementation. Learn about entities, repositories, and the query builder.',
                'nonSearchableField' => 'internal-ghi',
                'authorIndex' => null,
            ],
            [
                'searchableTextField' => 'Security in Web Applications',
                'searchableContentField' => 'Web security is crucial. Topics include SQL injection prevention, XSS attacks, CSRF protection, and secure session management.',
                'nonSearchableField' => 'classified-jkl',
                'authorIndex' => 2,
            ],
        ];

        foreach ($testData as $index => $data) {
            $entity = new SearchTestEntity();
            $entity->setSearchableTextField($data['searchableTextField']);
            $entity->setSearchableContentField($data['searchableContentField']);
            $entity->setNonSearchableField($data['nonSearchableField']);

            if (null !== $data['authorIndex']) {
                $entity->setAuthor($authors[$data['authorIndex']]);
            }

            $manager->persist($entity);
            $this->addReference('searchTest'.$index, $entity);
        }
    }

    private function addActionTestFixtures(ObjectManager $manager): void
    {
        // create test entities with various active/deletable states for testing actions
        $testData = [
            // active and deletable entities
            ['name' => 'Active Deletable Item 1', 'isActive' => true, 'isDeletable' => true],
            ['name' => 'Active Deletable Item 2', 'isActive' => true, 'isDeletable' => true],

            // active but not deletable entities (e.g., system entities)
            ['name' => 'System Entity 1', 'isActive' => true, 'isDeletable' => false],
            ['name' => 'System Entity 2', 'isActive' => true, 'isDeletable' => false],

            // inactive and deletable entities
            ['name' => 'Inactive Deletable Item 1', 'isActive' => false, 'isDeletable' => true],
            ['name' => 'Inactive Deletable Item 2', 'isActive' => false, 'isDeletable' => true],

            // inactive and not deletable entities
            ['name' => 'Archived System Entity', 'isActive' => false, 'isDeletable' => false],

            // additional varied entities for comprehensive testing
            ['name' => 'Featured Item', 'isActive' => true, 'isDeletable' => true],
            ['name' => 'Draft Item', 'isActive' => false, 'isDeletable' => true],
            ['name' => 'Protected Entity', 'isActive' => true, 'isDeletable' => false],
        ];

        foreach ($testData as $index => $data) {
            $entity = new ActionTestEntity();
            $entity->setName($data['name']);
            $entity->setIsActive($data['isActive']);
            $entity->setIsDeletable($data['isDeletable']);

            $manager->persist($entity);
            $this->addReference('actionTest'.$index, $entity);
        }
    }

    private function addProjectDomainSearchFixtures(ObjectManager $manager): void
    {
        // create release categories (separate entities because ProjectRelease→category is OneToOne)
        $categoryNames = ['Major', 'Minor', 'Major'];
        $categories = [];
        foreach ($categoryNames as $index => $name) {
            $category = new ProjectReleaseCategory();
            $category->setName($name);
            $manager->persist($category);
            $categories[$index] = $category;
        }

        // create releases
        $releaseData = [
            ['name' => 'v1.0', 'categoryIndex' => 0],
            ['name' => 'v1.1', 'categoryIndex' => 1],
            ['name' => 'v2.0', 'categoryIndex' => 2],
        ];
        $releases = [];
        foreach ($releaseData as $index => $data) {
            $release = new ProjectRelease();
            $release->setName($data['name']);
            $release->setCategory($categories[$data['categoryIndex']]);
            $manager->persist($release);
            $releases[$index] = $release;
        }

        // create developers
        $developerNames = ['Alice Dev', 'Bob Dev', 'Carol Dev'];
        $developers = [];
        foreach ($developerNames as $index => $name) {
            $developer = new Developer();
            $developer->setName($name);
            $manager->persist($developer);
            $developers[$index] = $developer;
        }

        // create projects
        $defaultDate = new \DateTime('2024-01-01 10:00:00');
        $defaultDateImmutable = new \DateTimeImmutable('2024-01-01 10:00:00');
        $defaultTime = new \DateTime('10:00:00');
        $defaultTimeImmutable = new \DateTimeImmutable('10:00:00');

        $projectData = [
            ['name' => 'Alpha Project', 'amount' => 1000, 'currency' => 'USD', 'developerIndex' => 0, 'releaseIndex' => 0],
            ['name' => 'Beta Project', 'amount' => 2000, 'currency' => 'EUR', 'developerIndex' => 1, 'releaseIndex' => 1],
            ['name' => 'Gamma Project', 'amount' => 3000, 'currency' => 'USD', 'developerIndex' => 2, 'releaseIndex' => 2],
            ['name' => 'Delta Project', 'amount' => 500, 'currency' => 'GBP', 'developerIndex' => 0, 'releaseIndex' => null],
        ];

        foreach ($projectData as $data) {
            $project = new Project();
            $project->setName($data['name']);
            $project->setPrice((new Money())->setAmount($data['amount'])->setCurrency($data['currency']));
            $project->setLeadDeveloper($developers[$data['developerIndex']]);
            $project->setDescription('Test project '.$data['name']);
            $project->setStartDateMutable(clone $defaultDate);
            $project->setStartDateImmutable($defaultDateImmutable);
            $project->setStartDateTimeMutable(clone $defaultDate);
            $project->setStartDateTimeImmutable($defaultDateImmutable);
            $project->setStartDateTimeTzMutable(clone $defaultDate);
            $project->setStartDateTimeTzImmutable($defaultDateImmutable);
            $project->setCountInteger(1);
            $project->setCountSmallint(1);
            $project->setPriceDecimal('100');
            $project->setPriceFloat(100.0);
            $project->setStartTimeMutable(clone $defaultTime);
            $project->setStartTimeImmutable($defaultTimeImmutable);
            $project->setStatesSimpleArray(['active']);

            if (null !== $data['releaseIndex']) {
                $project->setLatestRelease($releases[$data['releaseIndex']]);
            }

            $manager->persist($project);
        }
    }
}
