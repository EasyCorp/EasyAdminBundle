<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Cache;

use Doctrine\Common\Cache\FilesystemCache;

/**
 * It provides a file system based cache exposing methods with the same names
 * as in the PSR-6 Cache standard. This will simplify the eventual replacement
 * of Doctrine Cache by Symfony Cache.
 */
class CacheManager extends FilesystemCache
{
    public function __construct($cacheDir)
    {
        parent::__construct($cacheDir);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getItem($key)
    {
        return parent::fetch($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasItem($key)
    {
        return parent::contains($key);
    }

    /**
     * @param string $key
     * @param mixed  $item
     * @param int    $lifetime
     *
     * @return bool
     */
    public function save($key, $item, $lifetime = 0)
    {
        return parent::save($key, $item, $lifetime);
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Cache\CacheManager', 'JavierEguiluz\Bundle\EasyAdminBundle\Cache\CacheManager', false);
