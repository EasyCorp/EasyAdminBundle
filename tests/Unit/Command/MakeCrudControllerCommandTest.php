<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Command;

use EasyCorp\Bundle\EasyAdminBundle\Command\MakeCrudControllerCommand;
use EasyCorp\Bundle\EasyAdminBundle\Maker\ClassMaker;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\MakerTestEntity;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class MakeCrudControllerCommandTest extends KernelTestCase
{
    private const SUGGESTED_FIELDS = <<<'PHP'
                    IdField::new('id')->hideOnForm(),
                    EmailField::new('email'),
                    TextareaField::new('description')->hideOnIndex(),
                    ChoiceField::new('status'),
                    TextField::new('publicId')->onlyOnDetail(),
                    DateTimeField::new('createdAt')->hideOnForm(),
                    DateTimeField::new('updatedAt')->onlyOnDetail(),
                    AssociationField::new('relatedEntity'),
        PHP;

    private string $projectDir;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->projectDir = sprintf('%s/easyadmin_make_crud_%s', sys_get_temp_dir(), bin2hex(random_bytes(8)));

        $kernel = self::bootKernel();
        $command = new MakeCrudControllerCommand($this->projectDir, new ClassMaker($kernel, $this->projectDir), self::getContainer()->get('doctrine'));
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->projectDir);

        parent::tearDown();
    }

    public function testFieldsOption(): void
    {
        $this->commandTester->execute(['entity_fqcn' => MakerTestEntity::class, '--fields' => 'id,email,status'], ['interactive' => false]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertSame(<<<'PHP'
            <?php

            namespace App\Controller\Admin;

            use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
            use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
            use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
            use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
            use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
            use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\MakerTestEntity;

            /**
             * @extends AbstractCrudController<MakerTestEntity>
             */
            class MakerTestEntityCrudController extends AbstractCrudController
            {
                public static function getEntityFqcn(): string
                {
                    return MakerTestEntity::class;
                }

                public function configureFields(string $pageName): iterable
                {
                    return [
                        // a fieldset groups the fields below it in forms and on the detail page
                        // FormField::addFieldset('Details'),
                        IdField::new('id')->hideOnForm(),
                        EmailField::new('email'),
                        ChoiceField::new('status'),
                    ];
                }
            }

            PHP, $this->getGeneratedCode('MakerTestEntityCrudController'));
    }

    public function testFieldsOptionKeepsTheOrderAndAcceptsPropertiesNotSuggested(): void
    {
        $this->commandTester->execute(['entity_fqcn' => MakerTestEntity::class, '--fields' => 'status, password,id'], ['interactive' => false]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString(<<<'PHP'
                        // FormField::addFieldset('Details'),
                        ChoiceField::new('status'),
                        TextField::new('password')->onlyOnForms(),
                        IdField::new('id')->hideOnForm(),
                    ];
            PHP, $this->getGeneratedCode('MakerTestEntityCrudController'));
    }

    public function testDefaultAnswerSelectsTheSuggestedProperties(): void
    {
        $this->commandTester->setInputs(['', '', '']);
        $this->commandTester->execute(['entity_fqcn' => MakerTestEntity::class]);

        $this->commandTester->assertCommandIsSuccessful();
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Which properties do you want to show in this CRUD controller?', $display);
        $this->assertStringContainsString('[createdAt    ] DateTimeField, hidden on forms', $display);
        $this->assertStringContainsString('[password     ] TextField, only on forms (not suggested)', $display);

        $generatedCode = $this->getGeneratedCode('MakerTestEntityCrudController');
        $this->assertStringContainsString("// FormField::addFieldset('Details'),\n".self::SUGGESTED_FIELDS."\n        ];", $generatedCode);
        $this->assertStringNotContainsString('password', $generatedCode);
    }

    public function testNonInteractiveModeSelectsTheSuggestedProperties(): void
    {
        $this->commandTester->execute(['entity_fqcn' => MakerTestEntity::class], ['interactive' => false]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString("// FormField::addFieldset('Details'),\n".self::SUGGESTED_FIELDS."\n        ];", $this->getGeneratedCode('MakerTestEntityCrudController'));
    }

    public function testPropertiesTypedInTheAnswerKeepTheirOrder(): void
    {
        $this->commandTester->setInputs(['status, email', '', '']);
        $this->commandTester->execute(['entity_fqcn' => MakerTestEntity::class]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString(<<<'PHP'
                        // FormField::addFieldset('Details'),
                        ChoiceField::new('status'),
                        EmailField::new('email'),
                    ];
            PHP, $this->getGeneratedCode('MakerTestEntityCrudController'));
    }

    public function testAllAndNoneAnswersCantBeCombinedWithPropertyNames(): void
    {
        $this->commandTester->setInputs(['all,email', 'email', '', '']);
        $this->commandTester->execute(['entity_fqcn' => MakerTestEntity::class]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('The "all" and "none" answers can\'t be combined with property names.', $this->commandTester->getDisplay());
        $this->assertStringContainsString(<<<'PHP'
                        // FormField::addFieldset('Details'),
                        EmailField::new('email'),
                    ];
            PHP, $this->getGeneratedCode('MakerTestEntityCrudController'));
    }

    #[TestWith([['--no-fields' => true], []])]
    #[TestWith([[], ['none', '', '']])]
    public function testSkippingTheFieldsGeneratesACommentedExample(array $options, array $inputs): void
    {
        $this->commandTester->setInputs($inputs);
        $this->commandTester->execute(['entity_fqcn' => MakerTestEntity::class, ...$options], ['interactive' => [] !== $inputs]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertSame(<<<'PHP'
            <?php

            namespace App\Controller\Admin;

            use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
            use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
            use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
            use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
            use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\MakerTestEntity;

            /**
             * @extends AbstractCrudController<MakerTestEntity>
             */
            class MakerTestEntityCrudController extends AbstractCrudController
            {
                public static function getEntityFqcn(): string
                {
                    return MakerTestEntity::class;
                }

                /*
                public function configureFields(string $pageName): iterable
                {
                    return [
                        IdField::new('id'),
                        TextField::new('title'),
                        TextEditorField::new('description'),
                    ];
                }
                */
            }

            PHP, $this->getGeneratedCode('MakerTestEntityCrudController'));
    }

    public function testEntityIsImportedWithAnAliasWhenItsNameIsUsedByAField(): void
    {
        $this->commandTester->execute(['entity_fqcn' => FormField::class, '--fields' => 'id'], ['interactive' => false]);

        $this->commandTester->assertCommandIsSuccessful();
        $generatedCode = $this->getGeneratedCode('FormFieldCrudController');
        $this->assertStringContainsString("use EasyCorp\\Bundle\\EasyAdminBundle\\Field\\FormField;\n", $generatedCode);
        $this->assertStringContainsString("use EasyCorp\\Bundle\\EasyAdminBundle\\Tests\\Functional\\Apps\\DefaultApp\\Entity\\Synthetic\\FormField as FormFieldEntity;\n", $generatedCode);
        $this->assertStringContainsString(' * @extends AbstractCrudController<FormFieldEntity>', $generatedCode);
        $this->assertStringContainsString('return FormFieldEntity::class;', $generatedCode);
    }

    #[TestWith(['id,unknownProperty', 'doesn\'t have these properties or this command doesn\'t support their Doctrine types: unknownProperty. Pick some of these properties: id, email, description, status, publicId, createdAt, updatedAt, password, relatedEntity.'])]
    #[TestWith([' , ', 'The "--fields" option doesn\'t include any property name.'])]
    public function testInvalidFieldsOption(string $fieldsOption, string $expectedError): void
    {
        $this->commandTester->execute(['entity_fqcn' => MakerTestEntity::class, '--fields' => $fieldsOption], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString($expectedError, $this->getNormalizedDisplay());
        $this->assertDirectoryDoesNotExist($this->projectDir);
    }

    public function testFieldsAndNoFieldsOptionsCantBeCombined(): void
    {
        $this->commandTester->execute(['entity_fqcn' => MakerTestEntity::class, '--fields' => 'id', '--no-fields' => true], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('The "--fields" and "--no-fields" options can\'t be used at the same time.', $this->getNormalizedDisplay());
        $this->assertDirectoryDoesNotExist($this->projectDir);
    }

    public function testNonInteractiveModeRequiresTheEntity(): void
    {
        $this->commandTester->execute([], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Pass the FQCN of the Doctrine entity as the first argument of this command when running it in non-interactive mode.', $this->getNormalizedDisplay());
        $this->assertDirectoryDoesNotExist($this->projectDir);
    }

    private function getGeneratedCode(string $className): string
    {
        $filePath = sprintf('%s/src/Controller/Admin/%s.php', $this->projectDir, $className);
        $this->assertFileExists($filePath);

        exec(sprintf('%s -l %s 2>&1', escapeshellarg(\PHP_BINARY), escapeshellarg($filePath)), $lintOutput, $lintExitCode);
        $this->assertSame(0, $lintExitCode, implode("\n", $lintOutput));

        return file_get_contents($filePath);
    }

    private function getNormalizedDisplay(): string
    {
        return preg_replace('/\s+/', ' ', $this->commandTester->getDisplay());
    }
}
