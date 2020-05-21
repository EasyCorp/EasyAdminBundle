<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver\AdminContextResolver;
use EasyCorp\Bundle\EasyAdminBundle\Command\MakeAdminDashboardCommand;
use EasyCorp\Bundle\EasyAdminBundle\Command\MakeAdminMigrationCommand;
use EasyCorp\Bundle\EasyAdminBundle\Command\MakeCrudControllerCommand;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\AdminContextListener;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\CrudResponseListener;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\ExceptionListener;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ActionFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ChoiceConfigurator;
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
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use EasyCorp\Bundle\EasyAdminBundle\Security\SecurityVoter;
use EasyCorp\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

return static function (ContainerConfigurator $container) {
    $services = $container->services()
        ->defaults()->private();

    $services
        ->set(MakeAdminMigrationCommand::class)->public()
            ->arg(0, ref(Migrator::class))
            ->arg(1, '%kernel.project_dir%')
            ->tag('console.command')

        ->set(MakeAdminDashboardCommand::class)->public()
            ->arg(0, ref(ClassMaker::class))
            ->arg(1, '%kernel.project_dir%')
            ->tag('console.command')

        ->set(MakeCrudControllerCommand::class)->public()
            ->arg(0, ref(ClassMaker::class))
            ->arg(1, ref('doctrine'))
            ->tag('console.command')

        ->set(ClassMaker::class)
            ->arg(0, ref(KernelInterface::class))
            ->arg(1, '%kernel.project_dir%')

        ->set(Migrator::class)

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

        ->set(CollectionTypeExtension::class)
            ->tag('form.type_extension')

        ->set(AuthorizationChecker::class)
            ->arg(0, ref('security.authorization_checker')->nullOnInvalid())

        ->set(IntlFormatter::class)

        ->set(AdminContextProvider::class)
            ->arg(0, ref('request_stack'))

        ->set(AdminContextResolver::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->tag('controller.argument_value_resolver')

        ->set(AdminContextListener::class)
            ->arg(0, ref(AdminContextFactory::class))
            ->arg(1, ref(DashboardControllerRegistry::class))
            ->arg(2, ref('controller_resolver'))
            ->arg(3, ref('twig'))
            ->tag('kernel.event_listener', ['event' => ControllerEvent::class])

        ->set(CrudResponseListener::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref('twig'))
            ->tag('kernel.event_listener', ['event' => ViewEvent::class])

        ->set(AdminContextFactory::class)
            ->arg(0, ref('translator'))
            ->arg(1, ref('security.token_storage')->nullOnInvalid())
            ->arg(2, ref(MenuFactory::class))
            ->arg(3, tagged_iterator('ea.crud_controller'))
            ->arg(4, ref(EntityFactory::class))

        ->set(DashboardControllerRegistry::class)
            ->arg(0, '%kernel.secret%')
            ->arg(1, tagged_iterator('ea.dashboard_controller'))

        ->set(CrudUrlGenerator::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref('router.default'))

        ->set(MenuFactory::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref(DashboardControllerRegistry::class))
            ->arg(2, ref(AuthorizationChecker::class))
            ->arg(3, ref('translator'))
            ->arg(4, ref('router'))
            ->arg(5, ref('security.logout_url_generator'))
            ->arg(6, ref(CrudUrlGenerator::class))

        ->set(EntityRepository::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref('doctrine'))
            ->arg(2, ref(FormFactory::class))

        ->set(EntityFactory::class)
            ->arg(0, ref(FieldFactory::class))
            ->arg(1, ref(ActionFactory::class))
            ->arg(2, ref(AuthorizationChecker::class))
            ->arg(3, ref('doctrine'))
            ->arg(4, ref('event_dispatcher'))

        ->set(EntityPaginator::class)
            ->arg(0, ref(CrudUrlGenerator::class))
            ->arg(1, ref(EntityFactory::class))

        ->set(EntityUpdater::class)
            ->arg(0, ref('property_accessor'))

        ->set(PaginatorFactory::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref(EntityPaginator::class))

        ->set(FormFactory::class)
            ->arg(0, ref('form.factory'))
            ->arg(1, ref(CrudUrlGenerator::class))

        ->set(FieldFactory::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, ref(AuthorizationChecker::class))
            ->arg(2, \function_exists('tagged') ? tagged('ea.field_configurator') : tagged_iterator('ea.field_configurator'))

        ->set(FieldProvider::class)
            ->arg(0, ref(AdminContextProvider::class))

        ->set(FilterFactory::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->arg(1, \function_exists('tagged') ? tagged('ea.filter_configurator') : tagged_iterator('ea.filter_configurator'))

        ->set(FiltersFormType::class)
            ->tag('form.type', ['alias' => 'ea_filters'])

        ->set(ChoiceFilterConfigurator::class)
            ->tag('ea.filter_configurator')

        ->set(CommonFilterConfigurator::class)
            ->tag('ea.filter_configurator', ['priority' => 9999])

        ->set(ComparisonFilterConfigurator::class)
            ->tag('ea.filter_configurator')

        ->set(DateTimeFilterConfigurator::class)
            ->tag('ea.filter_configurator')

        ->set(EntityFilterConfigurator::class)
            ->tag('ea.filter_configurator')

        ->set(NumericFilterConfigurator::class)
            ->tag('ea.filter_configurator')

        ->set(TextFilterConfigurator::class)
            ->tag('ea.filter_configurator')

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
            ->arg(0, ref('form.type_guesser.doctrine'))
            ->tag('form.type', ['alias' => 'ea_crud'])

        ->set(ArrayConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(AssociationConfigurator::class)
            ->arg(0, ref(EntityFactory::class))
            ->arg(1, ref(CrudUrlGenerator::class))
            ->arg(2, ref(TranslatorInterface::class))
            ->tag('ea.field_configurator')

        ->set(AvatarConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(BooleanConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(CodeEditorConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(CollectionConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(CommonPostConfigurator::class)
            ->arg(0, ref(AdminContextProvider::class))
            ->tag('ea.field_configurator', ['priority' => -9999])

        ->set(CommonPreConfigurator::class)
            ->arg(0, ref('translator'))
            ->arg(1, ref('property_accessor'))
            ->tag('ea.field_configurator', ['priority' => 9999])

        ->set(CountryConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(CurrencyConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(DateTimeConfigurator::class)
            ->arg(0, ref(IntlFormatter::class))
            ->tag('ea.field_configurator')

        ->set(EmailConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(FormConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(IdConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(ImageConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(LanguageConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(LocaleConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(MoneyConfigurator::class)
            ->arg(0, ref(IntlFormatter::class))
            ->arg(1, ref('property_accessor'))
            ->tag('ea.field_configurator')

        ->set(NumberConfigurator::class)
            ->arg(0, ref(IntlFormatter::class))
            ->tag('ea.field_configurator')

        ->set(PercentConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(ChoiceConfigurator::class)
            ->arg(0, ref('translator'))
            ->tag('ea.field_configurator')

        ->set(SlugConfigurator::class)
            ->arg(0, ref('translator'))
            ->tag('ea.field_configurator')

        ->set(TelephoneConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(TextConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(TextEditorConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(TimezoneConfigurator::class)
            ->tag('ea.field_configurator')

        ->set(UrlConfigurator::class)
            ->tag('ea.field_configurator')
    ;
};
