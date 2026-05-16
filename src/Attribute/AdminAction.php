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
        @trigger_deprecation('easycorp/easyadmin-bundle', '5.0.1', 'The "%s()" attribute is deprecated and will be removed in EasyAdmin 5.1.0. Use the #[AdminRoute] attribute instead.', __METHOD__);
    }
}
