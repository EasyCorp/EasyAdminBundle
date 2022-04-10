<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver\AdminContextResolver;
use EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver\BatchActionDtoResolver;
use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheWarmer;
use EasyCorp\Bundle\EasyAdminBundle\Command\MakeAdminDashboardCommand;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\IntegerConfigurator;
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
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\NullConfigurator as NullFilterConfigurator;
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
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityPaginator;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Provider\FieldProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\UrlSigner;
use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use EasyCorp\Bundle\EasyAdminBundle\Security\SecurityVoter;
use EasyCorp\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension;
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
        ->set(MakeAdminDashboardCommand::class)->public()
            ->arg(0, service(ClassMaker::class))
            ->arg(1, param('kernel.project_dir'))
            ->tag('console.command')

        ->set(MakeCrudControllerCommand::class)->public()
            ->arg(0, param('kernel.project_dir'))
            ->arg(1, service(ClassMaker::class))
            ->arg(2, service('doctrine'))
            ->tag('console.command')

        ->set(ClassMaker::class)
            ->arg(0, service(KernelInterface::class))
            ->arg(1, param('kernel.project_dir'))

        ->set(CacheWarmer::class)
            ->arg(0, service('router'))
            ->tag('kernel.cache_warmer')

        ->set(DataCollector::class)
            ->arg(0, service(AdminContextProvider::class))
            ->tag('data_collector', ['id' => 'easyadmin', 'template' => '@EasyAdmin/inspector/data_collector.html.twig'])

        ->set(ExceptionListener::class)
            ->arg(0, '%kernel.debug%')
            ->arg(1, service(AdminContextProvider::class))
            ->arg(2, service('twig'))
            ->tag('kernel.event_listener', ['event' => 'kernel.exception', 'priority' => -64])

        ->set(EasyAdminTwigExtension::class)
            // I don't know if we truly need the locator to get a new instance of the
            // service whenever we generate a new URL, Maybe it's enough with the route parameter
            // initialization done after generating each URL
            ->arg(0, service('service_locator_'.AdminUrlGenerator::class))
            ->arg(1, new Reference('security.csrf.token_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->tag('twig.extension')

        ->set(EaCrudFormTypeExtension::class)
            ->arg(0, service(AdminContextProvider::class))
            ->tag('form.type_extension')

        ->set(CollectionTypeExtension::class)
            ->tag('form.type_extension')

        ->set(AuthorizationChecker::class)
            ->arg(0, new Reference('security.authorization_checker', ContainerInterface::NULL_ON_INVALID_REFERENCE))

        ->set(IntlFormatter::class)

        ->set(AdminContextProvider::class)
            ->arg(0, service('request_stack'))

        ->set(AdminContextResolver::class)
            ->arg(0, service(AdminContextProvider::class))
            ->tag('controller.argument_value_resolver')

        ->set(BatchActionDtoResolver::class)
            ->arg(0, service(AdminContextProvider::class))
            ->arg(1, service(AdminUrlGenerator::class))
            ->tag('controller.argument_value_resolver')

        ->set(AdminRouterSubscriber::class)
            ->arg(0, service(AdminContextFactory::class))
            ->arg(1, service(DashboardControllerRegistry::class))
            ->arg(2, service(CrudControllerRegistry::class))
            ->arg(3, service(ControllerFactory::class))
            ->arg(4, service('controller_resolver'))
            ->arg(5, service('router'))
            ->arg(6, service('router'))
            ->arg(7, service('twig'))
            ->tag('kernel.event_subscriber')

        ->set(ControllerFactory::class)
            ->arg(0, service(DashboardControllerRegistry::class))
            ->arg(1, service(CrudControllerRegistry::class))
            ->arg(2, service('controller_resolver'))

        ->set(CrudResponseListener::class)
            ->arg(0, service(AdminContextProvider::class))
            ->arg(1, service('twig'))
            ->tag('kernel.event_listener', ['event' => ViewEvent::class])

        ->set(AdminContextFactory::class)
            ->arg(0, '%kernel.cache_dir%')
            ->arg(1, service('translator'))
            ->arg(2, new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->arg(3, service(MenuFactory::class))
            ->arg(4, service(CrudControllerRegistry::class))
            ->arg(5, service(EntityFactory::class))

        ->set(AdminUrlGenerator::class)
            // I don't know if we truly need the share() method to get a new instance of the
            // service whenever we generate a new URL. Maybe it's enough with the route parameter
            // initialization done after generating each URL
            ->share(false)
            ->arg(0, service(AdminContextProvider::class))
            ->arg(1, service('router.default'))
            ->arg(2, service(DashboardControllerRegistry::class))
            ->arg(3, service(CrudControllerRegistry::class))

        ->set('service_locator_'.AdminUrlGenerator::class, ServiceLocator::class)
            ->args([[AdminUrlGenerator::class => service(AdminUrlGenerator::class)]])
            ->tag('container.service_locator')

        ->set(UrlSigner::class)
            ->arg(0, '%kernel.secret%')

        ->set(MenuFactory::class)
            ->arg(0, service(AdminContextProvider::class))
            ->arg(1, service(AuthorizationChecker::class))
            ->arg(2, service('translator'))
            ->arg(3, service('router'))
            ->arg(4, service('security.logout_url_generator'))
            ->arg(5, service(AdminUrlGenerator::class))

        ->set(EntityRepository::class)
            ->arg(0, service(AdminContextProvider::class))
            ->arg(1, service('doctrine'))
            ->arg(2, service(EntityFactory::class))
            ->arg(3, service(FormFactory::class))
            ->arg(4, service('event_dispatcher'))

        ->set(EntityFactory::class)
            ->arg(0, service(FieldFactory::class))
            ->arg(1, service(ActionFactory::class))
            ->arg(2, service(AuthorizationChecker::class))
            ->arg(3, service('doctrine'))
            ->arg(4, service('event_dispatcher'))

        ->set(EntityPaginator::class)
            ->arg(0, service(AdminUrlGenerator::class))
            ->arg(1, service(EntityFactory::class))

        ->set(EntityUpdater::class)
            ->arg(0, service('property_accessor'))
            ->arg(1, service('validator'))

        ->set(PaginatorFactory::class)
            ->arg(0, service(AdminContextProvider::class))
            ->arg(1, service(EntityPaginator::class))

        ->set(FormFactory::class)
            ->arg(0, service('form.factory'))

        ->set(FieldFactory::class)
            ->arg(0, service(AdminContextProvider::class))
            ->arg(1, service(AuthorizationChecker::class))
            ->arg(2, tagged_iterator(EasyAdminExtension::TAG_FIELD_CONFIGURATOR))

        ->set(FieldProvider::class)
            ->arg(0, service(AdminContextProvider::class))

        ->set(FilterFactory::class)
            ->arg(0, service(AdminContextProvider::class))
            ->arg(1, tagged_iterator(EasyAdminExtension::TAG_FILTER_CONFIGURATOR))

        ->set(FiltersFormType::class)
            ->tag('form.type', ['alias' => 'ea_filters'])

        ->set(FileUploadType::class)
            ->arg(0, param('kernel.project_dir'))
            ->tag('form.type')

        ->set(ChoiceFilterConfigurator::class)

        ->set(CommonFilterConfigurator::class)
            ->tag(EasyAdminExtension::TAG_FILTER_CONFIGURATOR, ['priority' => 9999])

        ->set(ComparisonFilterConfigurator::class)

        ->set(DateTimeFilterConfigurator::class)

        ->set(EntityFilterConfigurator::class)

        ->set(NullFilterConfigurator::class)

        ->set(NumericFilterConfigurator::class)

        ->set(TextFilterConfigurator::class)

        ->set(ActionFactory::class)
            ->arg(0, service(AdminContextProvider::class))
            ->arg(1, service(AuthorizationChecker::class))
            ->arg(2, service('translator'))
            ->arg(3, service(AdminUrlGenerator::class))
            ->arg(4, new Reference('security.csrf.token_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE))

        ->set(SecurityVoter::class)
            ->arg(0, service(AuthorizationChecker::class))
            ->arg(1, service(AdminContextProvider::class))
            ->tag('security.voter')

        ->set(CrudFormType::class)
            ->arg(0, service('form.type_guesser.doctrine'))
            ->tag('form.type', ['alias' => 'ea_crud'])

        ->set(ArrayConfigurator::class)

        ->set(AssociationConfigurator::class)
            ->arg(0, service(EntityFactory::class))
            ->arg(1, service(AdminUrlGenerator::class))
            ->arg(2, service(TranslatorInterface::class))

        ->set(AvatarConfigurator::class)

        ->set(BooleanConfigurator::class)
            ->arg(0, service(AdminUrlGenerator::class))
            ->arg(1, new Reference('security.csrf.token_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE))

        ->set(CodeEditorConfigurator::class)

        ->set(CollectionConfigurator::class)

        ->set(CommonPostConfigurator::class)
            ->arg(0, service(AdminContextProvider::class))
            ->arg(1, '%kernel.charset%')
            ->tag(EasyAdminExtension::TAG_FIELD_CONFIGURATOR, ['priority' => -9999])

        ->set(CommonPreConfigurator::class)
            ->arg(0, service('translator'))
            ->arg(1, service('property_accessor'))
            ->tag(EasyAdminExtension::TAG_FIELD_CONFIGURATOR, ['priority' => 9999])

        ->set(CountryConfigurator::class)
            ->arg(0, service('assets.packages'))

        ->set(CurrencyConfigurator::class)

        ->set(DateTimeConfigurator::class)
            ->arg(0, service(IntlFormatter::class))

        ->set(EmailConfigurator::class)

        ->set(FormConfigurator::class)

        ->set(IdConfigurator::class)

        ->set(ImageConfigurator::class)
            ->arg(0, param('kernel.project_dir'))

        ->set(IntegerConfigurator::class)

        ->set(LanguageConfigurator::class)

        ->set(LocaleConfigurator::class)

        ->set(MoneyConfigurator::class)
            ->arg(0, service(IntlFormatter::class))
            ->arg(1, service('property_accessor'))

        ->set(NumberConfigurator::class)
            ->arg(0, service(IntlFormatter::class))

        ->set(PercentConfigurator::class)

        ->set(ChoiceConfigurator::class)
            ->arg(0, service('translator'))

        ->set(SlugConfigurator::class)
            ->arg(0, service('translator'))

        ->set(TelephoneConfigurator::class)

        ->set(TextConfigurator::class)

        ->set(TextEditorConfigurator::class)

        ->set(TimezoneConfigurator::class)

        ->set(UrlConfigurator::class)
    ;
};
