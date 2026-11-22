<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component\Fixtures;

/**
 * Some anonymous components accept any backed enum case whose value is one
 * of the strings they support.
 */
enum StringOption: string
{
    case Block = 'block';
    case Error = 'error';
}
