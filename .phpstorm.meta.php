<?php

namespace PHPSTORM_META {
    use EasyCorp\Bundle\EasyAdminBundle\Contracts\Factory\ActionFactoryInterface;
    use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
    use EasyCorp\Bundle\EasyAdminBundle\Factory\ActionFactoryInterface;
    use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
    use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
    use EasyCorp\Bundle\EasyAdminBundle\Factory\PaginatorFactory;
    use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
    use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
    use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
    use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;

    override(\Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait::get(), map([
        ActionFactoryInterface::class => ActionFactoryInterface::class,
        AdminContextProvider::class => AdminContextProvider::class,
        AdminUrlGenerator::class => AdminUrlGenerator::class,
        CrudUrlGenerator::class => CrudUrlGenerator::class,
        EntityFactory::class => EntityFactory::class,
        EntityRepository::class => EntityRepository::class,
        EntityUpdater::class => EntityUpdater::class,
        FormFactory::class => FormFactory::class,
        PaginatorFactory::class => PaginatorFactory::class,
    ]));
}
