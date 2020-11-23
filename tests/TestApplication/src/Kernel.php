<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication;

if (version_compare(\Symfony\Component\HttpKernel\Kernel::MAJOR_VERSION, 5, '<')) {
    class_alias(KernelForSymfony4::class, Kernel::class);
} else {
    class_alias(KernelForSymfony5::class, Kernel::class);
}

if (false) {
    class Kernel
    {
    }
}
