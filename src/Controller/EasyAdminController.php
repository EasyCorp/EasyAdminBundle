<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\FilterRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Search\Autocomplete;
use EasyCorp\Bundle\EasyAdminBundle\Search\Paginator;
use EasyCorp\Bundle\EasyAdminBundle\Search\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The controller used to render all the default EasyAdmin actions.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EasyAdminController extends AbstractController
{
    use AdminControllerTrait;

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'easyadmin.autocomplete' => Autocomplete::class,
            'easyadmin.config.manager' => ConfigManager::class,
            'easyadmin.paginator' => Paginator::class,
            'easyadmin.query_builder' => QueryBuilder::class,
            'easyadmin.property_accessor' => PropertyAccessorInterface::class,
            'easyadmin.filter.registry' => FilterRegistry::class,
            'easyadmin.security.authorization_checker' => AuthorizationChecker::class,
            'event_dispatcher' => EventDispatcherInterface::class,
        ]);
    }
}
