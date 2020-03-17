<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Command\MakeAdminDashboardCommand;
use EasyCorp\Bundle\EasyAdminBundle\Command\MakeAdminMigrationCommand;
use EasyCorp\Bundle\EasyAdminBundle\Command\MakeAdminResourceCommand;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\AdminContextListener;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\CrudResponseListener;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\ExceptionListener;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ActionFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\PaginatorFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\AssociationConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\AvatarConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\BooleanConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CollectionConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CommonPostConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CommonPreConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CountryConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\CurrencyConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\DateTimeConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\EmailConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ImageConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\LanguageConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\LocaleConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\MoneyConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\NumberConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\PercentConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\SelectConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\TelephoneConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\TextConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\TimezoneConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\UrlConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Form\Extension\EaCollectionTypeExtension;
use EasyCorp\Bundle\EasyAdminBundle\Form\Extension\EaCrudFormTypeExtension;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\FilterRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use EasyCorp\Bundle\EasyAdminBundle\Inspector\DataCollector;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityPaginator;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use EasyCorp\Bundle\EasyAdminBundle\Security\SecurityVoter;
use EasyCorp\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\DependencyInjection\DependencyInjectionExtension;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

return static function (ContainerConfigurator $container) {
    $services = $container->services()
        ->defaults()->private();

    $services
        ->set(MakeAdminMigrationCommand::class)->public()
            ->tag('console.command', ['command' => 'make:admin:migration'])

        ->set(MakeAdminDashboardCommand::class)->public()
            ->tag('console.command', ['command' => 'make:admin:dashboard'])

        ->set(MakeAdminResourceCommand::class)->public()
            ->tag('console.command', ['command' => 'make:admin:resource'])

        ->set(DataCollector::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->tag('data_collector', ['id' => 'easyadmin', 'template' => '@EasyAdmin/inspector/data_collector.html.twig'])

        ->set(ExceptionListener::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref('twig'))
            ->tag('kernel.event_listener', ['event' => 'kernel.exception', 'priority' => -64])

        ->set(EasyAdminTwigExtension::class)
            ->arg(0, ref(CrudUrlGenerator::class))
            ->arg(1, ref(TranslatorInterface::class)->nullOnInvalid())
            ->tag('twig.extension')

        ->set(EaCrudFormTypeExtension::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->tag('form.type_extension')

        ->set(EaCollectionTypeExtension::class)
            ->tag('form.type_extension')

        ->set(AuthorizationChecker::class)
            ->arg(0, ref('security.authorization_checker')->nullOnInvalid())

        ->set(IntlFormatter::class)

        ->set(AdminContextProvider::class)
            ->arg(0, ref('request_stack'))

        ->set(AdminContextListener::class)
            ->arg(0, ref(AdminContextFactory::class))
            ->arg(1, ref('controller_resolver'))
            ->arg(2, ref('twig'))
            ->tag('kernel.event_listener', ['event' => ControllerEvent::class])

        ->set(CrudResponseListener::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref('twig'))
            ->tag('kernel.event_listener', ['event' => ViewEvent::class])

        ->set(AdminContextFactory::class)
            ->arg(0, ref('security.token_storage')->nullOnInvalid())
            ->arg(1, ref(MenuFactory::class))
            ->arg(2, tagged_iterator('ea.crud_controller'))

        ->set(CrudUrlGenerator::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref('router.default'))

        ->set(MenuFactory::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref(AuthorizationChecker::class))
            ->arg(2, ref('translator'))
            ->arg(3, ref('router'))
            ->arg(4, ref('security.logout_url_generator'))
            ->arg(5, ref(CrudUrlGenerator::class))

        ->set(FilterRegistry::class)
            // arguments are injected using the FilterTypePass compiler pass
            ->arg(0, null) // $filterTypeMap collection
            ->arg(1, null) // $filterTypeGuesser iterator

        // TODO: ask Yonel about this because I don't understand anything about this service :|
        ->set('ea.filter.extension', DependencyInjectionExtension::class)
            // arguments are injected using the FilterTypePass compiler pass
            ->arg(0, null) // service locator with all services tagged with 'ea.filter.type'
            ->arg(1, [])
            ->arg(2, [])

        ->set(EntityRepository::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref('doctrine'))
            ->arg(2, ref('form.factory'))
            ->arg(3, ref(FilterRegistry::class))

        ->set(EntityFactory::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref(FieldFactory::class))
            ->arg(2, ref(ActionFactory::class))
            ->arg(3, ref(AuthorizationChecker::class))
            ->arg(4, ref('doctrine'))
            ->arg(5, ref('event_dispatcher'))

        ->set(EntityPaginator::class)
            ->arg(0, ref(CrudUrlGenerator::class))
            ->arg(1, ref(EntityFactory::class))

        ->set(EntityUpdater::class)
            ->arg(0, ref('property_accessor'))

        ->set(PaginatorFactory::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref(EntityPaginator::class))

        ->set(FormFactory::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref('form.factory'))
            ->arg(2, ref(CrudUrlGenerator::class))

        ->set(FieldFactory::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref(AuthorizationChecker::class))
            ->arg(2, \function_exists('tagged') ? tagged('ea.field_configurator') : tagged_iterator('ea.field_configurator'))

        ->set(ActionFactory::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref(AuthorizationChecker::class))
            ->arg(2, ref('translator'))
            ->arg(3, ref('router'))
            ->arg(4, ref(CrudUrlGenerator::class))

        ->set(SecurityVoter::class)
            ->arg(0, ref(AuthorizationChecker::class))
            ->arg(1, ref(AdminContextProvider::class))
            ->tag('security.voter')

        ->set(CrudFormType::class)
            ->arg(0, \function_exists('tagged') ? tagged('ea.form_type_configurator') : tagged_iterator('ea.form_type_configurator'))
            ->arg(1, ref('form.type_guesser.doctrine'))
            ->tag('form.type', ['alias' => 'ea_crud'])

        ->set(CommonPreConfigurator::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref('translator'))
            ->arg(2, ref('property_accessor'))
            ->tag('ea.field_configurator', ['priority' => 9999])

        ->set(CommonPostConfigurator::class)
            ->tag('ea.field_configurator', ['priority' => -9999])

        ->set(TextConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(ImageConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(DateTimeConfigurator::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref(IntlFormatter::class))
            ->tag('ea.field_configurator')

        ->set(CountryConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(BooleanConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(AvatarConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(EmailConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(TelephoneConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(UrlConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(LanguageConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(MoneyConfigurator::class)
            ->arg(0, ref(IntlFormatter::class))
            ->arg(1, ref('property_accessor'))
            ->tag('ea.field_configurator')

        ->set(CurrencyConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(PercentConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(AssociationConfigurator::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref(EntityFactory::class))
            ->arg(2, ref(CrudUrlGenerator::class))
            ->arg(3, ref(TranslatorInterface::class))
            ->tag('ea.field_configurator')

        ->set(SelectConfigurator::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref('translator'))
            ->tag('ea.field_configurator')

        ->set(TimezoneConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(LocaleConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(NumberConfigurator::class)
            ->arg(0, ref(IntlFormatter::class))
            ->tag('ea.field_configurator')

        ->set(CollectionConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(FiltersFormType::class)
            ->arg(0, \function_exists('tagged') ? tagged('ea.form_type_configurator') : tagged_iterator('ea.form_type_configurator'))
            ->tag('form.type', ['alias' => 'ea_filters'])
    ;
};
