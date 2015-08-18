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
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator;

class EasyAdminDataCollector extends DataCollector
{
    private $configurator;

    public function __construct(Configurator $configurator)
    {
        $this->configurator = $configurator;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $backendConfiguration = $this->configurator->getBackendConfig();
        $entityName = $request->query->get('entity');
        $currentEntityConfiguration = $entityName ? $backendConfiguration['entities'][$entityName] : null;

        $this->data = array(
            'num_entities' => count($backendConfiguration['entities']),
            'request_parameters' => $this->getEasyAdminParameters($request),
            'current_entity_configuration' => $currentEntityConfiguration,
            'backend_configuration' => $backendConfiguration,
        );
    }

    private function getEasyAdminParameters(Request $request)
    {
        if ('admin' !== $request->attributes->get('_route')) {
            return;
        }

        return array(
            'action' => $request->query->get('action'),
            'entity' => $request->query->get('entity'),
            'id' => $request->query->get('id'),
            'sort_field' => $request->query->get('sortField'),
            'sort_direction' => $request->query->get('sortDirection'),
        );
    }

    public function getNumEntities()
    {
        return $this->data['num_entities'];
    }

    public function getRequestParameters()
    {
        return $this->data['request_parameters'];
    }

    public function getCurrentEntityConfiguration()
    {
        return $this->data['current_entity_configuration'];
    }

    public function getBackendConfiguration()
    {
        return $this->data['backend_configuration'];
    }

    public function getName()
    {
        return 'easyadmin';
    }
}
