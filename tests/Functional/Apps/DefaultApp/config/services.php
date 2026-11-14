<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Upload\FlysystemNameAsDirectoryUploadCrudController;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('locale', 'en')
        ->set('easyadmin.ui_kit.enabled', true)
        ->set('test.flysystem_storage_dir', '%kernel.cache_dir%/flysystem_storage');

    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
    ;

    $services->load('EasyCorp\\Bundle\\EasyAdminBundle\\Tests\\Functional\\Apps\\DefaultApp\\', '../src/*')
        ->exclude('../{Entity,Tests,Kernel.php}');

    $services->load('EasyCorp\\Bundle\\EasyAdminBundle\\Tests\\Functional\\Apps\\DefaultApp\\Controller\\', '../src/Controller/')
        ->tag('controller.service_arguments');

    $services->set('test.flysystem_storage.name_as_directory', Filesystem::class)
        ->args([inline_service(LocalFilesystemAdapter::class)->args([param('test.flysystem_storage_dir')])])
        ->tag('flysystem.storage', ['storage' => FlysystemNameAsDirectoryUploadCrudController::STORAGE]);
};
