<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FieldTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestEntity;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Base class for functional tests of EasyAdmin field types.
 * Provides utilities to test field rendering and form submission.
 */
abstract class AbstractFieldFunctionalTest extends AbstractCrudTestCase
{
    protected EntityRepository $fieldTestEntities;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        $this->fieldTestEntities = $this->entityManager->getRepository(FieldTestEntity::class);
    }

    protected function tearDown(): void
    {
        unset($this->fieldTestEntities);
        parent::tearDown();
    }

    protected function getControllerFqcn(): string
    {
        return FieldTestEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    /**
     * Generates an index URL sorted by ID descending to ensure newly created entities appear first.
     */
    protected function generateIndexUrlSortedByIdDesc(): string
    {
        return $this->getCrudUrl('index', null, ['sort' => ['id' => 'DESC']]);
    }

    /**
     * Creates a new FieldTestEntity with the given field values.
     *
     * @param array<string, mixed> $fieldValues
     */
    protected function createFieldTestEntity(array $fieldValues = []): FieldTestEntity
    {
        $entity = new FieldTestEntity();

        foreach ($fieldValues as $fieldName => $value) {
            $setter = 'set'.ucfirst($fieldName);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * Asserts that a field renders correctly on the index page.
     *
     * @param string $fieldName     The property name of the field
     * @param mixed  $expectedValue The expected rendered value (text content)
     * @param int    $entityId      The entity ID to check
     */
    protected function assertFieldRendersCorrectlyOnIndex(string $fieldName, mixed $expectedValue, int $entityId): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entityId));
        static::assertCount(1, $entityRow, sprintf('Entity with ID %d not found in index', $entityId));

        // use data-column which contains the exact property name (more reliable than data-label which is translated)
        $fieldCell = $entityRow->filter(sprintf('td[data-column="%s"]', $fieldName));
        static::assertCount(1, $fieldCell, sprintf('Field cell for %s not found in entity row', $fieldName));

        if (null === $expectedValue) {
            // for null values, the cell should be empty or contain a placeholder
            static::assertTrue(
                '' === trim($fieldCell->text()) || '-' === trim($fieldCell->text()),
                sprintf('Field %s should render as empty for null value', $fieldName)
            );
        } else {
            static::assertStringContainsString(
                (string) $expectedValue,
                $fieldCell->text(),
                sprintf('Field %s did not render the expected value on index', $fieldName)
            );
        }
    }

    /**
     * Asserts that a field renders correctly on the detail page.
     *
     * @param string $fieldName     The property name of the field
     * @param mixed  $expectedValue The expected rendered value (text content)
     * @param int    $entityId      The entity ID to view
     */
    protected function assertFieldRendersCorrectlyOnDetail(string $fieldName, mixed $expectedValue, int $entityId): void
    {
        $crawler = $this->client->request('GET', $this->generateDetailUrl($entityId));

        // find the field group for this field
        $fieldGroups = $crawler->filter('.content-body .field-group');
        $fieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            // check if this is the field we're looking for (case-insensitive comparison)
            if (false !== stripos($label, $fieldName) || false !== stripos(str_replace(' ', '', $label), $fieldName)) {
                $fieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value');

                if (null === $expectedValue) {
                    static::assertTrue(
                        0 === $fieldValue->count() || '' === trim($fieldValue->text()) || '-' === trim($fieldValue->text()),
                        sprintf('Field %s should render as empty for null value on detail', $fieldName)
                    );
                } else {
                    static::assertStringContainsString(
                        (string) $expectedValue,
                        $fieldValue->text(),
                        sprintf('Field %s did not render the expected value on detail', $fieldName)
                    );
                }
                break;
            }
        }

        if (!$fieldFound) {
            static::fail(sprintf('Field %s not found on detail page', $fieldName));
        }
    }

    /**
     * Asserts that a field submits correctly on a form.
     *
     * @param string $fieldName     The property name of the field
     * @param mixed  $inputValue    The value to enter in the form
     * @param mixed  $expectedValue The expected value in the database after submission
     */
    protected function assertFieldSubmitsCorrectlyOnForm(string $fieldName, mixed $inputValue, mixed $expectedValue): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();

        // find the field input and set its value
        $formFieldName = sprintf('FieldTestEntity[%s]', $fieldName);
        if (isset($form[$formFieldName])) {
            $form[$formFieldName] = $inputValue;
        } else {
            static::fail(sprintf('Form field %s not found', $formFieldName));
        }

        $this->client->submit($form);

        // verify the value was saved correctly
        $entity = $this->fieldTestEntities->findOneBy([], ['id' => 'DESC']);
        static::assertNotNull($entity, 'Entity was not created');

        $getter = 'get'.ucfirst($fieldName);
        if (method_exists($entity, $getter)) {
            $actualValue = $entity->$getter();
            static::assertEquals(
                $expectedValue,
                $actualValue,
                sprintf('Field %s did not save the expected value', $fieldName)
            );
        } else {
            // try with 'is' prefix for boolean fields
            $getter = 'is'.ucfirst($fieldName);
            if (method_exists($entity, $getter)) {
                $actualValue = $entity->$getter();
                static::assertEquals(
                    $expectedValue,
                    $actualValue,
                    sprintf('Field %s did not save the expected value', $fieldName)
                );
            } else {
                static::fail(sprintf('Getter method for field %s not found', $fieldName));
            }
        }
    }

    /**
     * Asserts that a field exists in the form.
     *
     * @param string $fieldName The property name of the field
     */
    protected function assertFieldExistsInForm(string $fieldName): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $formFieldName = sprintf('FieldTestEntity[%s]', $fieldName);
        $field = $crawler->filter(sprintf('[name="%s"]', $formFieldName));

        static::assertGreaterThan(
            0,
            $field->count(),
            sprintf('Field %s not found in form', $fieldName)
        );
    }

    /**
     * Asserts that a field has a specific CSS class in the form.
     *
     * @param string $fieldName The property name of the field
     * @param string $cssClass  The expected CSS class
     */
    protected function assertFieldHasCssClassInForm(string $fieldName, string $cssClass): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $formFieldName = sprintf('FieldTestEntity[%s]', $fieldName);
        $field = $crawler->filter(sprintf('[name="%s"]', $formFieldName));

        static::assertStringContainsString(
            $cssClass,
            $field->attr('class') ?? '',
            sprintf('Field %s does not have CSS class %s', $fieldName, $cssClass)
        );
    }
}
