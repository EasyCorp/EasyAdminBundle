<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\DataCollector;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\Yaml\Yaml;

/**
 * Collects information about the requests related to EasyAdmin and displays
 * it both in the web debug toolbar and in the profiler.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EasyAdminDataCollector extends DataCollector
{
    /** @var ConfigManager */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
        $this->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = array(
            'num_entities' => 0,
            'request_parameters' => null,
            'current_entity_configuration' => null,
            'backend_configuration' => null,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        // 'admin' is the deprecated route name that will be removed in version 2.0.
        if (!in_array($request->attributes->get('_route'), array('easyadmin', 'admin'))) {
            return;
        }

        $backendConfig = $this->configManager->getBackendConfig();
        $entityName = $request->query->get('entity', null);
        $currentEntityConfig = array_key_exists($entityName, $backendConfig['entities']) ? $backendConfig['entities'][$entityName] : array();

        $this->data = array(
            'num_entities' => count($backendConfig['entities']),
            'request_parameters' => $this->getEasyAdminParameters($request),
            'current_entity_configuration' => $currentEntityConfig,
            'backend_configuration' => $backendConfig,
        );
    }

    /**
     * @param Request $request
     *
     * @return array|null
     */
    private function getEasyAdminParameters(Request $request)
    {
        return array(
            'action' => $request->query->get('action'),
            'entity' => $request->query->get('entity'),
            'id' => $request->query->get('id'),
            'sort_field' => $request->query->get('sortField'),
            'sort_direction' => $request->query->get('sortDirection'),
        );
    }

    /**
     * @return bool
     */
    public function isEasyAdminAction()
    {
        return isset($this->data['num_entities']) && 0 !== $this->data['num_entities'];
    }

    /**
     * @return int
     */
    public function getNumEntities()
    {
        return $this->data['num_entities'];
    }

    /**
     * @return array
     */
    public function getRequestParameters()
    {
        return $this->data['request_parameters'];
    }

    /**
     * @return array
     */
    public function getCurrentEntityConfig()
    {
        return $this->data['current_entity_configuration'];
    }

    /**
     * @return array
     */
    public function getBackendConfig()
    {
        return $this->data['backend_configuration'];
    }

    /**
     * It dumps the contents of the given variable. It tries several dumpers in
     * turn (VarDumper component, Yaml::dump, etc.) and if none is available, it
     * falls back to PHP's var_export().
     *
     * @param mixed $variable
     *
     * @return string
     */
    public function dump($variable)
    {
        if (class_exists('Symfony\Component\VarDumper\Dumper\HtmlDumper')) {
            $cloner = new VarCloner();
            $dumper = new HtmlDumper();

            $dumper->dump($cloner->cloneVar($variable), $output = fopen('php://memory', 'r+b'));
            $dumpedData = stream_get_contents($output, -1, 0);
        } elseif (class_exists('Symfony\Component\Yaml\Yaml')) {
            $dumpedData = sprintf('<pre class="sf-dump">%s</pre>', Yaml::dump((array) $variable, 1024));
        } else {
            $dumpedData = sprintf('<pre class="sf-dump">%s</pre>', var_export($variable, true));
        }

        return $dumpedData;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'easyadmin';
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\DataCollector\EasyAdminDataCollector', 'JavierEguiluz\Bundle\EasyAdminBundle\DataCollector\EasyAdminDataCollector', false);
