<?php

namespace EasyCorp\Bundle\EasyAdminBundle;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Action\ActionsExtensionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\UiKit\ComponentDocumentationReader;
use EasyCorp\Bundle\EasyAdminBundle\UiKit\TwigSourceHighlighter;
use EasyCorp\Bundle\EasyAdminBundle\UiKit\UiKitController;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EasyAdminBundle extends AbstractBundle
{
    public const VERSION = '5.8.0';
    public const TAG_CRUD_CONTROLLER = 'ea.crud_controller';
    public const TAG_DASHBOARD_CONTROLLER = 'ea.dashboard_controller';
    public const TAG_ADMIN_ROUTE_CONTROLLER = 'ea.admin_route_controller';
    public const TAG_FIELD_CONFIGURATOR = 'ea.field_configurator';
    public const TAG_FILTER_CONFIGURATOR = 'ea.filter_configurator';
    public const TAG_ACTIONS_EXTENSION = 'ea.actions_extension';
    public const PARAMETER_UI_KIT_ENABLED = 'easyadmin.ui_kit.enabled';

    /**
     * @phpstan-ignore missingType.iterableValue
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
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

        $configurator->import('../config/services.php');

        if ($this->isUiKitEnabled($container)) {
            $container->register(TwigSourceHighlighter::class, TwigSourceHighlighter::class);
            $container->register(ComponentDocumentationReader::class, ComponentDocumentationReader::class)
                ->setArguments([new Reference('twig')]);
            $container->register(UiKitController::class, UiKitController::class)
                ->setArguments([
                    new Reference('twig'),
                    new Reference(TwigSourceHighlighter::class),
                    new Reference(ComponentDocumentationReader::class),
                    new Reference(AdminContextProvider::class),
                    \dirname(__DIR__, 1).'/templates/ui_kit/components',
                    \dirname(__DIR__, 1).'/templates/components',
                ])
                ->addTag('controller.service_arguments')
                ->addTag(self::TAG_ADMIN_ROUTE_CONTROLLER);
        }
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('twig_component', [
            'defaults' => [
                'EasyCorp\\Bundle\\EasyAdminBundle\\Twig\\Component\\' => [
                    'template_directory' => '@EasyAdmin/components/',
                    'name_prefix' => 'ea',
                ],
            ],
        ]);

        /** @var string $projectDir */
        $projectDir = $container->getParameter('kernel.project_dir');

        $bundleTemplatesOverrideDir = $projectDir.'/templates/bundles/EasyAdminBundle/';
        $container->prependExtensionConfig('twig', [
            'paths' => is_dir($bundleTemplatesOverrideDir)
                ? [
                    'templates/bundles/EasyAdminBundle/' => 'ea',
                    \dirname(__DIR__).'/templates/' => 'ea',
                ]
                : [
                    \dirname(__DIR__).'/templates/' => 'ea',
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
