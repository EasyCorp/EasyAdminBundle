<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Normalizer;

/**
 * Normalizers transform the given backend configuration to normalize its
 * contents. This allows the end-user to use shortcuts and syntactic sugar
 * to define the backend configuration. Then, that configuration is normalized
 * to simplify its processing.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface NormalizerInterface
{
    /**
     * @param array $backendConfiguration
     *
     * @return array
     */
    public function normalize(array $backendConfiguration);
}
