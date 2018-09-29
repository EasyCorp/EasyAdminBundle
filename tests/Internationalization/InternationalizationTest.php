<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Internationalization;

use PHPUnit\Framework\TestCase;

class InternationalizationTest extends TestCase
{
    public function testXliffFiles()
    {
        $xlfFiles = glob(__DIR__.'/../../src/Resources/translations/*.*.xlf');
        foreach ($xlfFiles as $xlfFilePath) {
            $document = new \DOMDocument();
            $document->load($xlfFilePath);
            $isValid = @$document->schemaValidateSource($this->getSchemaContents());

            $this->assertTrue($isValid, sprintf('The %s file is valid according to XLIFF XSD.', basename($xlfFilePath)));
        }
    }

    /**
     * This is needed to avoid the delay introduced by w3.org to not saturate their
     * servers. This method can be replaced when we update Symfony version and the
     * lint:xliff command is available.
     */
    private function getSchemaContents()
    {
        $schemaContents = file_get_contents(__DIR__.'/xliff-core-1.2-strict.xsd');

        $localSchemaPath = __DIR__.'/xml.xsd';
        $localSchemaUri = 'file:///'.implode('/', array_map('rawurlencode', explode('/', $localSchemaPath)));
        $remoteSchemaUri = 'http://www.w3.org/2001/xml.xsd';

        $modifiedSchemaContents = str_replace($remoteSchemaUri, $localSchemaUri, $schemaContents);

        return $modifiedSchemaContents;
    }
}
