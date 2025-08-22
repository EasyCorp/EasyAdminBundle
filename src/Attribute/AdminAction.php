<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Attribute;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class AdminAction
{
    /**
     * @param array<string> $methods
     */
    public function __construct(
        public ?string $routePath = null,
        public ?string $routeName = null,
        public array $methods = ['GET'],
    ) {
    }
}
