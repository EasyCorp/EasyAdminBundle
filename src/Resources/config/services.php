<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver\AdminContextResolver;
use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheWarmer;
use EasyCorp\Bundle\EasyAdminBundle\Command\MakeAdminDashboardCommand;
use EasyCorp\Bundle\EasyAdminBundle\Command\MakeAdminMigrationCommand;
use EasyCorp\Bundle\EasyAdminBundle\Command\MakeCrudControllerCommand;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\EasyAdminExtension;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\AdminRouterSubscriber;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\CrudResponseListener;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\ExceptionListener;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ActionFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\PaginatorFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ArrayConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\AssociationConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\AvatarConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\BooleanConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ChoiceConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CodeEditorConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CollectionConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CommonPostConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CommonPreConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CountryConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CurrencyConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\DateTimeConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\EmailConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\FormConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\IdConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ImageConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\LanguageConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\LocaleConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\MoneyConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\NumberConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\PercentConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\SlugConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\TelephoneConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\TextConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\TextEditorConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\TimezoneConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\UrlConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\ChoiceConfigurator as ChoiceFilterConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\CommonConfigurator as CommonFilterConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\ComparisonConfigurator as ComparisonFilterConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\DateTimeConfigurator as DateTimeFilterConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\EntityConfigurator as EntityFilterConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\NumericConfigurator as NumericFilterConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\TextConfigurator as TextFilterConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Form\Extension\CollectionTypeExtension;
use EasyCorp\Bundle\EasyAdminBundle\Form\Extension\EaCrudFormTypeExtension;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use EasyCorp\Bundle\EasyAdminBundle\Inspector\DataCollector;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Maker\ClassMaker;
use EasyCorp\Bundle\EasyAdminBundle\Maker\Migrator;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityPaginator;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Provider\FieldProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\UrlSigner;
use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use EasyCorp\Bundle\EasyAdminBundle\Security\SecurityVoter;
use EasyCorp\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension;
use Symfony\Component\DependencyInjection\Compiler\AliasDeprecatedPublicServicesPass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

return static function (ContainerConfigurator $container) {
    $services = $container->services()
        ->defaults()->private()
        ->instanceof(FieldConfiguratorInterface::class)->tag(EasyAdminExtension::TAG_FIELD_CONFIGURATOR)
        ->instanceof(FilterConfiguratorInterface::class)->tag(EasyAdminExtension::TAG_FILTER_CONFIGURATOR);

    $services
        ->set(MakeAdminMigrationCommand::class)->public()
            ->arg(0, new Reference(Migrator::class))
            ->arg(1, '%kernel.project_dir%')
            ->tag('console.command')

        ->set(MakeAdminDashboardCommand::class)->public()
            ->arg(0, new Reference(ClassMaker::class))
            ->arg(1, '%kernel.project_dir%')
            ->tag('console.command')

        ->set(MakeCrudControllerCommand::class)->public()
            ->arg(0, '%kernel.project_dir%')
            ->arg(1, new Reference(ClassMaker::class))
            ->arg(2, new Reference('doctrine'))
            ->tag('console.command')

        ->set(ClassMaker::class)
            ->arg(0, new Reference(KernelInterface::class))
            ->arg(1, '%kernel.project_dir%')

        ->set(Migrator::class)

        ->set(CacheWarmer::class)
            ->arg(0, new Reference('router'))
            ->tag('kernel.cache_warmer')

        ->set(DataCollector::class)
            ->arg(0, new Reference(AdminContextProvider::class))
            ->tag('data_collector', ['id' => 'easyadmin', 'template' => '@EasyAdmin/inspector/data_collector.html.twig'])

        ->set(ExceptionListener::class)
            ->arg(0, '%kernel.debug%')
            ->arg(1, new Reference(AdminContextProvider::class))
            ->arg(2, new Reference('twig'))
            ->tag('kernel.event_listener', ['event' => 'kernel.exception', 'priority' => -64])

        ->set(EasyAdminTwigExtension::class)
            // I don't know if we truly need the locator to get a new instance of the
            // service whenever we generate a new URL, Maybe it's enough with the route parameter
            // initialization done after generating each URL
            ->arg(0, new Reference('service_locator_'.AdminUrlGenerator::class))
            ->tag('twig.extension')

        ->set(EaCrudFormTypeExtension::class)
            ->arg(0, new Reference(AdminContextProvider::class))
            ->tag('form.type_extension')

        ->set(CollectionTypeExtension::class)
            ->tag('form.type_extension')

        ->set(AuthorizationChecker::class)
            ->arg(0, new Reference('security.authorization_checker', ContainerInterface::NULL_ON_INVALID_REFERENCE))

        ->set(IntlFormatter::class)

        ->set(AdminContextProvider::class)
            ->arg(0, new Reference('request_stack'))

        ->set(AdminContextResolver::class)
            ->arg(0, new Reference(AdminContextProvider::class))
            ->tag('controller.argument_value_resolver')

        ->set(AdminRouterSubscriber::class)
            ->arg(0, new Reference(AdminContextFactory::class))
            ->arg(1, new Reference(DashboardControllerRegistry::class))
            ->arg(2, new Reference(CrudControllerRegistry::class))
            ->arg(3, new Reference(ControllerFactory::class))
            ->arg(4, new Reference('controller_resolver'))
            ->arg(5, new Reference('router'))
            ->arg(6, new Reference('router'))
            ->arg(7, new Reference('twig'))
            ->arg(8, new Reference(UrlSigner::class))
            ->tag('kernel.event_subscriber')

        ->set(ControllerFactory::class)
            ->arg(0, new Reference(DashboardControllerRegistry::class))
            ->arg(1, new Reference(CrudControllerRegistry::class))
            ->arg(2, new Reference('controller_resolver'))

        ->set(CrudResponseListener::class)
            ->arg(0, new Reference(AdminContextProvider::class))
            ->arg(1, new Reference('twig'))
            ->tag('kernel.event_listener', ['event' => ViewEvent::class])

        ->set(AdminContextFactory::class)
            ->arg(0, '%kernel.cache_dir%')
            ->arg(1, new Reference('translator'))
            ->arg(2, new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->arg(3, new Reference(MenuFactory::class))
            ->arg(4, new Reference(CrudControllerRegistry::class))
            ->arg(5, new Reference(EntityFactory::class))

        ->set(AdminUrlGenerator::class)
            // I don't know if we truly need the share() method to get a new instance of the
            // service whenever we generate a new URL. Maybe it's enough with the route parameter
            // initialization done after generating each URL
            ->share(false)
            ->arg(0, new Reference(AdminContextProvider::class))
            ->arg(1, new Reference('router.default'))
            ->arg(2, new Reference(DashboardControllerRegistry::class))
            ->arg(3, new Reference(CrudControllerRegistry::class))
            ->arg(4, new Reference(UrlSigner::class))

        ->set('service_locator_'.AdminUrlGenerator::class, ServiceLocator::class)
            ->args([[AdminUrlGenerator::class => new Reference(AdminUrlGenerator::class)]])
            ->tag('container.service_locator')

        ->set(UrlSigner::class)
            ->arg(0, '%kernel.secret%')

        ->set(MenuFactory::class)
            ->arg(0, new Reference(AdminContextProvider::class))
            ->arg(1, new Reference(AuthorizationChecker::class))
            ->arg(2, new Reference('translator'))
            ->arg(3, new Reference('router'))
            ->arg(4, new Reference('security.logout_url_generator'))
            ->arg(5, new Reference(AdminUrlGenerator::class))

        ->set(EntityRepository::class)
            ->arg(0, new Reference(AdminContextProvider::class))
            ->arg(1, new Reference('doctrine'))
            ->arg(2, new Reference(EntityFactory::class))
            ->arg(3, new Reference(FormFactory::class))

        ->set(EntityFactory::class)
            ->arg(0, new Reference(FieldFactory::class))
            ->arg(1, new Reference(ActionFactory::class))
            ->arg(2, new Reference(AuthorizationChecker::class))
            ->arg(3, new Reference('doctrine'))
            ->arg(4, new Reference('event_dispatcher'))

        ->set(EntityPaginator::class)
            ->arg(0, new Reference(AdminUrlGenerator::class))
            ->arg(1, new Reference(EntityFactory::class))

        ->set(EntityUpdater::class)
            ->arg(0, new Reference('property_accessor'))

        ->set(PaginatorFactory::class)
            ->arg(0, new Reference(AdminContextProvider::class))
            ->arg(1, new Reference(EntityPaginator::class))

        ->set(FormFactory::class)
            ->arg(0, new Reference('form.factory'))
            ->arg(1, new Reference(AdminUrlGenerator::class))

        ->set(FieldFactory::class)
            ->arg(0, new Reference(AdminContextProvider::class))
            ->arg(1, new Reference(AuthorizationChecker::class))
            ->arg(2, \function_exists('tagged')
                ? tagged(EasyAdminExtension::TAG_FIELD_CONFIGURATOR)
                : tagged_iterator(EasyAdminExtension::TAG_FIELD_CONFIGURATOR))

        ->set(FieldProvider::class)
            ->arg(0, new Reference(AdminContextProvider::class))

        ->set(FilterFactory::class)
            ->arg(0, new Reference(AdminContextProvider::class))
            ->arg(1, \function_exists('tagged')
                ? tagged(EasyAdminExtension::TAG_FILTER_CONFIGURATOR)
                : tagged_iterator(EasyAdminExtension::TAG_FILTER_CONFIGURATOR))

        ->set(FiltersFormType::class)
            ->tag('form.type', ['alias' => 'ea_filters'])

        ->set(FileUploadType::class)
            ->arg(0, '%kernel.project_dir%')
            ->tag('form.type')

        ->set(ChoiceFilterConfigurator::class)

        ->set(CommonFilterConfigurator::class)
            ->tag(EasyAdminExtension::TAG_FILTER_CONFIGURATOR, ['priority' => 9999])

        ->set(ComparisonFilterConfigurator::class)

        ->set(DateTimeFilterConfigurator::class)

        ->set(EntityFilterConfigurator::class)

        ->set(NumericFilterConfigurator::class)

        ->set(TextFilterConfigurator::class)

        ->set(ActionFactory::class)
            ->arg(0, new Reference(AdminContextProvider::class))
            ->arg(1, new Reference(AuthorizationChecker::class))
            ->arg(2, new Reference('translator'))
            ->arg(3, new Reference(AdminUrlGenerator::class))

        ->set(SecurityVoter::class)
            ->arg(0, new Reference(AuthorizationChecker::class))
            ->arg(1, new Reference(AdminContextProvider::class))
            ->tag('security.voter')

        ->set(CrudFormType::class)
            ->arg(0, new Reference('form.type_guesser.doctrine'))
            ->tag('form.type', ['alias' => 'ea_crud'])

        ->set(ArrayConfigurator::class)

        ->set(AssociationConfigurator::class)
            ->arg(0, new Reference(EntityFactory::class))
            ->arg(1, new Reference(AdminUrlGenerator::class))
            ->arg(2, new Reference(TranslatorInterface::class))

        ->set(AvatarConfigurator::class)

        ->set(BooleanConfigurator::class)
            ->arg(0, new Reference(AdminUrlGenerator::class))

        ->set(CodeEditorConfigurator::class)

        ->set(CollectionConfigurator::class)

        ->set(CommonPostConfigurator::class)
            ->arg(0, new Reference(AdminContextProvider::class))
            ->arg(1, '%kernel.charset%')
            ->tag(EasyAdminExtension::TAG_FIELD_CONFIGURATOR, ['priority' => -9999])

        ->set(CommonPreConfigurator::class)
            ->arg(0, new Reference('translator'))
            ->arg(1, new Reference('property_accessor'))
            ->tag(EasyAdminExtension::TAG_FIELD_CONFIGURATOR, ['priority' => 9999])

        ->set(CountryConfigurator::class)
            ->arg(0, new Reference('assets.packages'))

        ->set(CurrencyConfigurator::class)

        ->set(DateTimeConfigurator::class)
            ->arg(0, new Reference(IntlFormatter::class))

        ->set(EmailConfigurator::class)

        ->set(FormConfigurator::class)

        ->set(IdConfigurator::class)

        ->set(ImageConfigurator::class)
            ->arg(0, '%kernel.project_dir%')

        ->set(LanguageConfigurator::class)

        ->set(LocaleConfigurator::class)

        ->set(MoneyConfigurator::class)
            ->arg(0, new Reference(IntlFormatter::class))
            ->arg(1, new Reference('property_accessor'))

        ->set(NumberConfigurator::class)
            ->arg(0, new Reference(IntlFormatter::class))

        ->set(PercentConfigurator::class)

        ->set(ChoiceConfigurator::class)
            ->arg(0, new Reference('translator'))

        ->set(SlugConfigurator::class)
            ->arg(0, new Reference('translator'))

        ->set(TelephoneConfigurator::class)

        ->set(TextConfigurator::class)

        ->set(TextEditorConfigurator::class)

        ->set(TimezoneConfigurator::class)

        ->set(UrlConfigurator::class)
    ;

    $crudUrlGenerator = $services
        ->set(CrudUrlGenerator::class)
        ->arg(0, new Reference(AdminContextProvider::class))
        ->arg(1, new Reference('router.default'))
        ->arg(2, new Reference(UrlSigner::class))
        ->arg(3, new Reference(DashboardControllerRegistry::class))
        ->arg(4, new Reference(CrudControllerRegistry::class));

    if (class_exists(AliasDeprecatedPublicServicesPass::class)) {
        $crudUrlGenerator->deprecate('easycorp/easyadmin-bundle', '3.2.0', sprintf('The "%%service_id%%" service is deprecated, use "%s" instead.', AdminUrlGenerator::class));
    } else {
        $crudUrlGenerator->deprecate(sprintf('Since easycorp/easyadmin-bundle 3.2.0: The "%%service_id%% service is deprecated, use "%s" instead.', AdminUrlGenerator::class));
    }
};
