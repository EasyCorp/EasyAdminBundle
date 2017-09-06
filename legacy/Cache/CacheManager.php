<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Cache;

use Doctrine\Common\Cache\FilesystemCache;

@trigger_error('The '.__NAMESPACE__.'\CacheManager class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Cache\CacheManager class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Cache\CacheManager');

if (\false) {
    class CacheManager extends FilesystemCache
    {
    }
}
