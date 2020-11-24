<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Test;

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;

final class PhpUnitExtension implements BeforeFirstTestHook, AfterLastTestHook
{
    public function executeBeforeFirstTest(): void
    {
        // do nothing because these changes must be done before loading
        // PHP clases, so here it's too late. This is performed in the
        // tests/bootstrap.php file
    }

    public function executeAfterLastTest(): void
    {
        $this->restoreFinalClasses();
    }

    private function restoreFinalClasses(): void
    {
        foreach ($this->findSourceFiles() as $sourceFilePath) {
            $sourceFilePath = realpath($sourceFilePath);
            $sourceFileContents = file_get_contents($sourceFilePath);
            $sourceFileContentsWithFinalClasses = preg_replace(
                sprintf('/^%s final %s class (.*)$/m', preg_quote(EA_TEST_COMMENT_MARKER_START, '/'), preg_quote(EA_TEST_COMMENT_MARKER_END, '/')),
                'final class \1',
                $sourceFileContents
            );
            file_put_contents($sourceFilePath, $sourceFileContentsWithFinalClasses);
        }
    }

    private function findSourceFiles(): array
    {
        return glob(__DIR__.'/../**/*.php');
    }
}
