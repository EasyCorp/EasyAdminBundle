<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Maker;

interface ClassMakerInterface
{
    /**
     * @return string The path of the created file (relative to the project dir)
     */
    public function make(string $generatedFilePathPattern, string $skeletonName, array $skeletonParameters): string;
}
