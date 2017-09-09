<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

@trigger_error('The '.__NAMESPACE__.'\ConfigWarmer class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Cache\ConfigWarmer class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Cache\ConfigWarmer');

if (\false) {
    class ConfigWarmer implements CacheWarmerInterface
    {
    }
}
