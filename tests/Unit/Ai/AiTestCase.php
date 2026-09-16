<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Ai;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

abstract class AiTestCase extends TestCase
{
    protected const VERSION = '5.5.2-DEV';

    protected string $tempDir;
    protected string $projectDir;
    protected string $packageDir;
    protected Filesystem $filesystem;

    /**
     * @var string|false
     */
    private $originalTerminalWidth;

    protected function setUp(): void
    {
        // the terminal width is fixed so that the console output of the commands
        // is always wrapped at the same place, no matter where the tests run
        $this->originalTerminalWidth = getenv('COLUMNS');
        putenv('COLUMNS=240');

        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/ai/'.uniqid();
        $this->projectDir = $this->tempDir.'/project';
        $this->packageDir = $this->tempDir.'/package';

        $this->filesystem->mkdir($this->projectDir);
        $this->createSkillSource();
    }

    protected function tearDown(): void
    {
        putenv(false === $this->originalTerminalWidth ? 'COLUMNS' : 'COLUMNS='.$this->originalTerminalWidth);
        $this->filesystem->remove($this->tempDir);
    }

    protected function skillsSourceDir(): string
    {
        return $this->packageDir.'/skills';
    }

    protected function guidelinesTemplatePath(): string
    {
        return $this->packageDir.'/src/Resources/ai/guidelines.md';
    }

    protected function createSkillSource(): void
    {
        $this->filesystem->dumpFile($this->skillsSourceDir().'/easyadmin/SKILL.md', <<<'MD'
            ---
            name: easyadmin
            description: >-
              Creates and modifies EasyAdmin admin backends in Symfony applications.
            license: MIT
            metadata:
              author: EasyCorp
            ---

            # EasyAdmin

            Use the makers to generate dashboards and CRUD controllers.

            MD);

        $this->filesystem->dumpFile($this->skillsSourceDir().'/easyadmin/references/api.md', <<<'MD'
            # API reference

            AbstractCrudController::configureFields()

            MD);

        $this->filesystem->dumpFile($this->guidelinesTemplatePath(), <<<'MD'
            This project uses EasyAdmin %EASYADMIN_VERSION%.

            Read and follow the `easyadmin` skill at `%SKILL_PATH%`.

            MD);
    }

    /**
     * @return array<string, string> the path of each file relative to $directory => its contents
     */
    protected function snapshotOf(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[substr($file->getPathname(), 1 + \strlen($directory))] = file_get_contents($file->getPathname());
            }
        }
        ksort($files);

        return $files;
    }
}
