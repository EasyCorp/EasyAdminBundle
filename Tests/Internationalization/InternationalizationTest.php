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

class InternationalizationTest extends \PHPUnit_Framework_TestCase
{
    public function testXliffFiles()
    {
        $xlfFiles = glob(__DIR__.'/../../Resources/translations/*.*.xlf');
        foreach ($xlfFiles as $xlfFilePath) {
            $document = new \DOMDocument();
            $document->load($xlfFilePath);
            $this->assertTrue(
                $document->schemaValidate(__DIR__.'/xliff-core-1.2-strict.xsd'),
                sprintf('The %s file is valid according to XLIFF XSD.', basename($xlfFilePath))
            );
        }
    }
}
