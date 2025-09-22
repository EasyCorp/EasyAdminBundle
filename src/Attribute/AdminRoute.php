<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Attribute;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AdminRoute
{
    public function __construct(
        /**
         * @var string|null $path The path defined in this option is appended to the dashboard path and, for CRUD actions, also to the CRUD path (e.g. using '/invoice/send' here might result in /admin/invoice/send, /admin/orders/invoice/send, etc. depending on your backend)
         */
        public ?string $path = null,

        /**
         * @var string|null $name The route name defined here is appended to the dashboard route name and, for CRUD actions, also to the CRUD route name (e.g. using 'restock' here might result in admin_restock, admin_products_restock, etc. depending on your backend)
         */
        public ?string $name = null,

        /**
         * @var array{
         *     requirements?: array<string, string>,
         *     options?: array<string, mixed>,
         *     defaults?: array<string, mixed>,
         *     host?: string,
         *     methods?: array<string>|string,
         *     schemes?: array<string>|string,
         *     condition?: string,
         *     locale?: string,
         *     format?: string,
         *     utf8?: bool,
         *     stateless?: bool,
         * } $options Additional configuration options used when creating this admin route
         */
        public array $options = [],

        /**
         * @var class-string[]|false|null $allowedDashboards If defined, this admin route will be available only for the specified dashboards. Possible values:
         *                                - false (default): Not set - inherit from the #[AdminRoute] attribute defined at the class level (if any)
         *                                - null: Explicitly allow all dashboards (used to override the same option in the #[AdminRoute] attribute at class level)
         *                                - []: Explicitly allow no dashboards
         *                                - [FooDashboard::class, BarDashboard::class, ...]: Allow only specific dashboards
         */
        public array|false|null $allowedDashboards = false,

        /**
         * @var class-string[]|false|null $deniedDashboards If defined, this admin route won't be available for the specified dashboards. Possible values:
         *                                - false (default): Not set - inherit from the #[AdminRoute] attribute defined at the class level (if any)
         *                                - null: Explicitly deny none (used to override the same option in the #[AdminRoute] attribute at class level)
         *                                - []: Explicitly deny no dashboards
         *                                - [FooDashboard::class, BarDashboard::class, ...]: Deny specific dashboards
         */
        public array|false|null $deniedDashboards = false,
    ) {
    }
}
