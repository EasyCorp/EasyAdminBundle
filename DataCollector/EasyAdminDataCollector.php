<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class EasyAdminDataCollector extends DataCollector
{
    private $backendConfiguration;

    public function __construct($backendConfiguration = array())
    {
        $this->backendConfiguration = $backendConfiguration;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'num_entities' => count($this->backendConfiguration['entities']),
            'config_basic' => array(
                'site_name' => $this->backendConfiguration['site_name'],
            ),
            'config_formats' => $this->backendConfiguration['formats'],
            'config_design' => count($this->backendConfiguration['design']),
            'config_actions' => array(
                'disabled_actions' => $this->backendConfiguration['disabled_actions'],
                'list' => $this->backendConfiguration['list'],
                'edit' => $this->backendConfiguration['edit'],
                'new' => $this->backendConfiguration['new'],
                'show' => $this->backendConfiguration['show'],
            ),
            'config_entities' => $this->backendConfiguration['entities'],
        );
    }

    public function getNumEntities()
    {
        return $this->data['num_entities'];
    }

    public function getBasicConfiguration()
    {
        return $this->data['config_basic'];
    }

    public function getFormatsConfiguration()
    {
        return $this->data['config_formats'];
    }

    public function getDesignConfiguration()
    {
        return $this->data['config_design'];
    }

    public function getActionsConfiguration()
    {
        return $this->data['config_actions'];
    }

    public function getEntitiesConfiguration()
    {
        return $this->data['config_entities'];
    }

    public function getName()
    {
        return 'easyadmin';
    }
}
