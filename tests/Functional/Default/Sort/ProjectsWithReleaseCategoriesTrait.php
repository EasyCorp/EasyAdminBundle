<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\ProjectFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\ProjectReleaseCategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\ProjectReleaseFactory;

/**
 * Creates these projects, in this order, with their latest release category:
 * "Alpha Project" (Major), "Beta Project" (Minor), "Gamma Project" (Major) and
 * "Delta Project" (no release). Categories are created in that same order, so
 * their ids increase while their names don't.
 */
trait ProjectsWithReleaseCategoriesTrait
{
    /**
     * @var array<string, string|null>
     */
    private const RELEASE_CATEGORY_BY_PROJECT_NAME = [
        'Alpha Project' => 'Major',
        'Beta Project' => 'Minor',
        'Gamma Project' => 'Major',
        'Delta Project' => null,
    ];

    private function createProjectsWithReleaseCategories(): void
    {
        foreach (self::RELEASE_CATEGORY_BY_PROJECT_NAME as $projectName => $categoryName) {
            $latestRelease = null === $categoryName ? null : ProjectReleaseFactory::createOne([
                'category' => ProjectReleaseCategoryFactory::createOne(['name' => $categoryName]),
            ]);

            ProjectFactory::createOne(['name' => $projectName, 'latestRelease' => $latestRelease]);
        }
    }
}
