<?php

namespace EasyCorp\Bundle\EasyAdminBundle\DependencyInjection;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Action\ActionsExtensionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\UiKit\TwigSourceHighlighter;
use EasyCorp\Bundle\EasyAdminBundle\UiKit\UiKitController;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EasyAdminExtension extends Extension implements PrependExtensionInterface
{
    public const TAG_CRUD_CONTROLLER = 'ea.crud_controller';
    public const TAG_DASHBOARD_CONTROLLER = 'ea.dashboard_controller';
    public const TAG_ADMIN_ROUTE_CONTROLLER = 'ea.admin_route_controller';
    public const TAG_FIELD_CONFIGURATOR = 'ea.field_configurator';
    public const TAG_FILTER_CONFIGURATOR = 'ea.filter_configurator';
    public const TAG_ACTIONS_EXTENSION = 'ea.actions_extension';
    public const PARAMETER_UI_KIT_ENABLED = 'easyadmin.ui_kit.enabled';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(AdminRoute::class,
            // @phpstan-ignore-next-line argument.type The reflection subtypes specify where the attribute can be used
            static function (Definition $definition, AdminRoute $attribute, \ReflectionClass|\ReflectionMethod $reflection): void {
                $definition->addTag(self::TAG_ADMIN_ROUTE_CONTROLLER);
            });

        $container->registerForAutoconfiguration(DashboardControllerInterface::class)
            ->addTag(self::TAG_DASHBOARD_CONTROLLER);

        $container->registerForAutoconfiguration(CrudControllerInterface::class)
            ->addTag(self::TAG_CRUD_CONTROLLER);

        $container->registerForAutoconfiguration(FieldConfiguratorInterface::class)
            ->addTag(self::TAG_FIELD_CONFIGURATOR);

        $container->registerForAutoconfiguration(FilterConfiguratorInterface::class)
            ->addTag(self::TAG_FILTER_CONFIGURATOR);

        $container->registerForAutoconfiguration(ActionsExtensionInterface::class)
            ->addTag(self::TAG_ACTIONS_EXTENSION);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        if ($this->isUiKitEnabled($container)) {
            $container->register(TwigSourceHighlighter::class, TwigSourceHighlighter::class);
            $container->register(UiKitController::class, UiKitController::class)
                ->setArguments([
                    new Reference('twig'),
                    new Reference(TwigSourceHighlighter::class),
                    \dirname(__DIR__, 2).'/templates/ui_kit/components',
                ])
                ->addTag('controller.service_arguments')
                ->addTag(self::TAG_ADMIN_ROUTE_CONTROLLER);
        }
    }

    public function prepend(ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('twig_component', [
            'defaults' => [
                'EasyCorp\\Bundle\\EasyAdminBundle\\Twig\\Component\\' => [
                    'template_directory' => '@EasyAdmin/components/',
                    'name_prefix' => 'ea',
                ],
            ],
        ]);

        /** @var string $projectDir */
        $projectDir = $builder->getParameter('kernel.project_dir');

        $bundleTemplatesOverrideDir = $projectDir.'/templates/bundles/EasyAdminBundle/';
        $builder->prependExtensionConfig('twig', [
            'paths' => is_dir($bundleTemplatesOverrideDir)
                ? [
                    'templates/bundles/EasyAdminBundle/' => 'ea',
                    \dirname(__DIR__).'/../templates/' => 'ea',
                ]
                : [
                    \dirname(__DIR__).'/../templates/' => 'ea',
                ],
        ]);
    }

    /**
     * The UI Kit is a development tool, so it's only enabled in debug mode unless the
     * application sets the 'easyadmin.ui_kit.enabled' parameter (e.g. a public demo).
     *
     * The parameter decides which services and routes exist, so it's read when compiling the
     * container. Environment variables are rejected instead of resolved at that moment, because
     * their compile-time value would stay in the cached container even after changing them.
     */
    private function isUiKitEnabled(ContainerBuilder $container): bool
    {
        if (!$container->hasParameter(self::PARAMETER_UI_KIT_ENABLED)) {
            return (bool) $container->getParameter('kernel.debug');
        }

        $isEnabled = $container->getParameterBag()->resolveValue($container->getParameter(self::PARAMETER_UI_KIT_ENABLED));
        $container->resolveEnvPlaceholders($isEnabled, null, $usedEnvVars);
        if ([] !== ($usedEnvVars ?? [])) {
            throw new InvalidArgumentException(sprintf('The "%s" parameter can\'t use environment variables because it\'s read when compiling the container. Set it to true or false instead.', self::PARAMETER_UI_KIT_ENABLED));
        }

        return (bool) $isEnabled;
    }
}
