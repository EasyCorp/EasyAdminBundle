<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;

/**
 * This class is entirely based on Symfony\Component\HttpKernel\UriSigner.
 * (c) Fabien Potencier <fabien@symfony.com> - MIT License.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class UrlSigner
{
    private $kernelSecret;

    public function __construct(string $kernelSecret)
    {
        $this->kernelSecret = $kernelSecret;
    }

    /**
     * Signs a URL adding a query parameter with a hash generated
     * with the values of some of the URL query parameters.
     */
    public function sign(string $url): string
    {
        $urlParts = parse_url($url);
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);
        } else {
            $queryParams = [];
        }

        $queryParams[EA::URL_SIGNATURE] = $this->computeHash($this->getQueryParamsToSign($queryParams));

        return $this->buildUrl($urlParts, $queryParams);
    }

    /**
     * Checks that a URL contains a valid signature.
     */
    public function check(string $url): bool
    {
        $urlParts = parse_url($url);
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);
        } else {
            $queryParams = [];
        }

        // this differs from Symfony's UriSigner behavior: if the URL doesn't contain any
        // query parameters, then consider that the signature is OK (even if there's no signature)
        if ([] === $queryParams) {
            return true;
        }

        if (!isset($queryParams[EA::URL_SIGNATURE]) || empty($queryParams[EA::URL_SIGNATURE])) {
            return false;
        }

        $expectedHash = $queryParams[EA::URL_SIGNATURE];
        $calculatedHash = $this->computeHash($this->getQueryParamsToSign($queryParams));

        return hash_equals($calculatedHash, $expectedHash);
    }

    private function computeHash(array $queryParameters): string
    {
        // Base64 hashes include some characters which are not compatible with
        // query strings, so we replace them to avoid encoding them in the query string
        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode(hash_hmac('sha256', http_build_query($queryParameters), $this->kernelSecret, true))
        );
    }

    /**
     * Instead of signing the entire URL, including all its query parameters,
     * sign only a few parameters that can be used to attack a backend by:.
     *
     *   * Enumerating all entities of certain type (EA::ENTITY_ID)
     *   * Accessing all application entities (EA::CRUD_CONTROLLER_FQCN)
     *   * Accessing any CRUD controller method (EA::CRUD_ACTION)
     *   * Accessing any application route (EA::ROUTE_NAME)
     *   * Meddling with the parameters of any application route (EA::ROUTE_PARAMS)
     *
     * The rest of query parameters are not relevant for the signature (EA::PAGE, EA::SORT, etc.)
     * or are dynamically set by the user (EA::QUERY, EA::FILTERS, etc.) so they can't be
     * included in a signature calculated before providing that data.
     */
    private function getQueryParamsToSign(array $queryParams): array
    {
        $signableQueryParams = array_intersect_key($queryParams, [
            EA::CRUD_ACTION => 0,
            EA::CRUD_CONTROLLER_FQCN => 1,
            EA::ENTITY_ID => 2,
            EA::ROUTE_NAME => 3,
            EA::ROUTE_PARAMS => 4,
        ]);

        ksort($signableQueryParams, SORT_STRING);

        return $signableQueryParams;
    }

    private function buildUrl(array $urlParts, array $queryParams = []): string
    {
        ksort($queryParams, SORT_STRING);
        $urlParts['query'] = http_build_query($queryParams, '', '&');

        $scheme = isset($urlParts['scheme']) ? $urlParts['scheme'].'://' : '';
        $host = $urlParts['host'] ?? '';
        $port = isset($urlParts['port']) ? ':'.$urlParts['port'] : '';
        $user = $urlParts['user'] ?? '';
        $pass = isset($urlParts['pass']) ? ':'.$urlParts['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = $urlParts['path'] ?? '';
        $query = isset($urlParts['query']) && $urlParts['query'] ? '?'.$urlParts['query'] : '';
        $fragment = isset($urlParts['fragment']) ? '#'.$urlParts['fragment'] : '';

        return $scheme.$user.$pass.$host.$port.$path.$query.$fragment;
    }
}
