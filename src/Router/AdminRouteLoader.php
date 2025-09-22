<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Router\AdminRouteGeneratorInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AdminRouteLoader extends Loader
{
    public const ROUTE_LOADER_TYPE = 'easyadmin.routes';
    /** @internal don't use this in your application */
    public const PRETTY_URLS_CONTEXT_FILE_NAME = 'easyadmin/application_uses_pretty_urls.txt';

    public function __construct(
        private AdminRouteGeneratorInterface $adminRouteGenerator,
        private Filesystem $filesystem,
        private string $buildDir,
    ) {
        parent::__construct(null);
    }

    public function supports($resource, ?string $type = null): bool
    {
        return self::ROUTE_LOADER_TYPE === $type;
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        // this is ugly, but I can't find any other way of solving this problem.
        // Details about the problem to solve: EasyAdmin must support both ugly and
        // pretty URLs and the user must not configure anything to enable pretty URLs.
        // In som parts of this bundle, we need to know if the custom route loader was
        // run to decide if pretty URLs should be used or not.
        //
        // This can't be solved in any of these ways:
        //   * the router doesn't provide any feature to know if a route loader was run
        //   * we can't set a container parameter dynamically because ParameterBag is frozen
        //   * we can't store something in the cache because it's not reliable (see https://github.com/symfony/symfony/issues/59445)
        //   * we can't create a "marker service" set inside the custom route loader because
        //     this only works when the route loader is run the first time (next times, the routes are cached)
        //   * etc.
        //
        // The only reliable way is to create a "marker file" in the cache that is not deleted.
        // All this will be greatly simplified in EasyAdmin 5.x when pretty URLs will be mandatory.
        $this->filesystem->dumpFile(sprintf('%s/%s', $this->buildDir, self::PRETTY_URLS_CONTEXT_FILE_NAME), '');

        return $this->adminRouteGenerator->generateAll();
    }
}
