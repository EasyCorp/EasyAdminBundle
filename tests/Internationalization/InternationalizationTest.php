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

use AppKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class InternationalizationTest extends TestCase
{
    public function testXliffFiles()
    {
        $application = new Application(new AppKernel('default_backend', true));
        $application->setAutoExit(false);

        $input = new ArrayInput(['command' => 'lint:xliff', 'filename' => 'src/Resources/translations']);
        $output = new BufferedOutput();

        $returnCode = $application->run($input, $output);
        $this->assertSame(0, $returnCode, $output->fetch());
    }
}
