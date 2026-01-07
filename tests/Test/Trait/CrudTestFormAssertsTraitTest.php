<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Test\Trait;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestFormAsserts;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestUrlGeneration;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\SecureDashboardController;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CrudTestFormAssertsTraitTest extends WebTestCase
{
    use CrudTestFormAsserts;
    use CrudTestUrlGeneration;

    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;
    private AdminUrlGeneratorInterface $adminUrlGenerator;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        $container = static::getContainer();
        $this->adminUrlGenerator = $container->get(AdminUrlGenerator::class);
    }

    /**
     * @return string returns the tested Controller Fqcn
     */
    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    /**
     * @return string returns the tested Controller Fqcn
     */
    protected function getDashboardFqcn(): string
    {
        return SecureDashboardController::class;
    }

    /**
     * @dataProvider formFields
     */
    public function testAssertFormFieldExists(string $fieldName): void
    {
        $this->client->request('GET', self::generateNewFormUrl());

        self::assertFormFieldExists($fieldName);
    }

    /**
     * @dataProvider formUnknownFields
     */
    public function testAssertFormFieldExistsWithNonExistingFieldRaisesError(string $fieldName): void
    {
        $this->client->request('GET', self::generateNewFormUrl());

        self::expectException(AssertionFailedError::class);
        self::assertFormFieldExists($fieldName);
    }

    /**
     * @dataProvider formUnknownFields
     */
    public function testAssertFormFieldNotExists(string $fieldName): void
    {
        $this->client->request('GET', self::generateNewFormUrl());

        self::assertFormFieldNotExists($fieldName);
    }

    /**
     * @dataProvider formFields
     */
    public function testAssertFormFieldNotExistsWithExistingFieldsRaisesError(string $fieldName): void
    {
        $this->client->request('GET', self::generateNewFormUrl());

        self::expectException(AssertionFailedError::class);
        self::assertFormFieldNotExists($fieldName);
    }

    public static function formFields(): \Generator
    {
        yield ['name'];
        yield ['slug'];
        yield ['active'];
    }

    public static function formUnknownFields(): \Generator
    {
        yield ['id'];
        yield ['Technician'];
        yield ['unknown_field'];
    }

    /**
     * @dataProvider formFieldsLabels
     */
    public function testAssertFormFieldHasLabel(string $fieldName, string $fieldLabel): void
    {
        $this->client->request('GET', self::generateNewFormUrl());

        self::assertFormFieldHasLabel($fieldName, $fieldLabel);
    }

    /**
     * @dataProvider formFieldsIncorrectLabels
     */
    public function testAssertFormFieldHasLabelWithIncorrectLabelsRaisesError(string $fieldName, string $fieldLabel): void
    {
        $this->client->request('GET', self::generateNewFormUrl());

        self::expectException(AssertionFailedError::class);
        self::assertFormFieldHasLabel($fieldName, $fieldLabel);
    }

    /**
     * @dataProvider formIncorrectFieldsWithLabels
     */
    public function testAssertFormFieldHasLabelWithIncorrectFieldsRaisesError(string $fieldName, string $fieldLabel): void
    {
        $this->client->request('GET', self::generateNewFormUrl());

        self::expectException(AssertionFailedError::class);
        self::assertFormFieldHasLabel($fieldName, $fieldLabel);
    }

    /**
     * @dataProvider formFieldsIncorrectLabels
     */
    public function testAssertFormFieldNotHasLabel(string $fieldName, string $fieldLabel): void
    {
        $this->client->request('GET', self::generateNewFormUrl());

        self::assertFormFieldNotHasLabel($fieldName, $fieldLabel);
    }

    /**
     * @dataProvider formFieldsLabels
     */
    public function testAssertFormFieldNotHasLabelCorrectLabelsRaisesError(string $fieldName, string $fieldLabel): void
    {
        $this->client->request('GET', self::generateNewFormUrl());

        self::expectException(AssertionFailedError::class);
        self::assertFormFieldNotHasLabel($fieldName, $fieldLabel);
    }

    public static function formFieldsLabels(): \Generator
    {
        yield ['name', 'Name'];
        yield ['slug', 'Slug'];
        yield ['active', 'Active'];
    }

    public static function formFieldsIncorrectLabels(): \Generator
    {
        yield ['name', 'name'];
        yield ['slug', 'slug'];
        yield ['active', 'active'];
    }

    public static function formIncorrectFieldsWithLabels(): \Generator
    {
        yield ['incorrect_field', 'Name'];
        yield ['incorrect_field', 'incorrect_value'];
    }
}
