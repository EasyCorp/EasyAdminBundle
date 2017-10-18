<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

/**
 * Wrapper to access private service argument_resolver as public
 *
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class ArgumentResolver implements ArgumentResolverInterface
{
    /**
     * @var ArgumentResolverInterface
     */
    private $argumentResolver;

    public function __construct(ArgumentResolverInterface $argumentResolver)
    {
        $this->argumentResolver = $argumentResolver;
    }

    public function getArguments(Request $request, $controller)
    {
        return $this->argumentResolver->getArguments($request, $controller);
    }
}
