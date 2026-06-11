<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Attribute;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AdminCrud
{
    public function __construct(
        public ?string $routePath = null,
        public ?string $routeName = null,
    ) {
        @trigger_deprecation('easycorp/easyadmin-bundle', '4.29.5', 'The "%s()" attribute is deprecated and will be removed in EasyAdmin 5.1.0. Use the #[AdminRoute] attribute instead.', __METHOD__);
    }
}
