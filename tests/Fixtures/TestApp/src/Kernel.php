<?php

namespace TestApp;

if (version_compare(\Symfony\Component\HttpKernel\Kernel::MAJOR_VERSION, 5, '<')) {
    class_alias('TestApp\KernelForSymfony4', 'TestApp\Kernel');
} else {
    class_alias('TestApp\KernelForSymfony5', 'TestApp\Kernel');
}

if (false) {
    class Kernel
    {
    }
}
