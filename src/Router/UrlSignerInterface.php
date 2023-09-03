<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

/**
 * This class is entirely based on Symfony\Component\HttpKernel\UriSigner.
 * (c) Fabien Potencier <fabien@symfony.com> - MIT License.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface UrlSignerInterface
{
    /**
     * Signs a URL adding a query parameter with a hash generated
     * with the values of some of the URL query parameters.
     */
    public function sign(string $url): string;

    /**
     * Checks that a URL contains a valid signature.
     */
    public function check(string $url): bool;
}
