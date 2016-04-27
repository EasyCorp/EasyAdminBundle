<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Cache;

use Doctrine\Common\Cache\FilesystemCache;

/**
 * ...
 */
class CacheManager extends FilesystemCache
{
    public function __construct($cacheDir)
    {
        parent::__construct($cacheDir);
    }

    public function getItem($key)
    {
        return parent::fetch($key);
    }

    public function hasItem($key)
    {
        return parent::contains($key);
    }

    public function save($key, $item)
    {
        parent::save($key, $item);
    }
}
