<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Exception\UndefinedEntityException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class ConfigManager implements ConfigManagerInterface
{
    /** @var array */
    private $backendConfig;
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;
    /** @var array */
    private $originalBackendConfig;
    /** @var ConfigPassInterface[] */
    private $configPasses;

    public function __construct(PropertyAccessorInterface $propertyAccessor, array $originalBackendConfig)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->originalBackendConfig = $originalBackendConfig;
    }

    /**
     * @param ConfigPassInterface $configPass
     */
    public function addConfigPass(ConfigPassInterface $configPass)
    {
        $this->configPasses[] = $configPass;
    }

    /**
     * {@inheritdoc}
     */
    public function getBackendConfig(string $propertyPath = null)
    {
        if (null === $this->backendConfig) {
            $this->backendConfig = $this->doProcessConfig($this->originalBackendConfig);
        }

        if (empty($propertyPath)) {
            return $this->backendConfig;
        }

        // turns 'design.menu' into '[design][menu]', the format required by PropertyAccess
        $propertyPath = '['.str_replace('.', '][', $propertyPath).']';

        return $this->propertyAccessor->getValue($this->backendConfig, $propertyPath);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfig(string $entityName): array
    {
        $backendConfig = $this->getBackendConfig();
        if (!isset($backendConfig['entities'][$entityName])) {
            throw new UndefinedEntityException(['entity_name' => $entityName]);
        }

        return $backendConfig['entities'][$entityName];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigByClass(string $fqcn): ?array
    {
        $backendConfig = $this->getBackendConfig();
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            if ($entityConfig['class'] === $fqcn) {
                return $entityConfig;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getActionConfig(string $entityName, string $view, string $action): array
    {
        try {
            $entityConfig = $this->getEntityConfig($entityName);
        } catch (\Exception $e) {
            $entityConfig = [];
        }

        return $entityConfig[$view]['actions'][$action] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function isActionEnabled(string $entityName, string $view, string $action): bool
    {
        $entityConfig = $this->getEntityConfig($entityName);

        return !\in_array($action, $entityConfig['disabled_actions'], true) && array_key_exists($action, $entityConfig[$view]['actions']);
    }

    /**
     * It processes the given backend configuration to generate the fully
     * processed configuration used in the application.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function doProcessConfig($backendConfig): array
    {
        foreach ($this->configPasses as $configPass) {
            $backendConfig = $configPass->process($backendConfig);
        }

        return $backendConfig;
    }
}
