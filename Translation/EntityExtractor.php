<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Translation;

use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\ExtractorInterface;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator;

/**
 * The extractor for the automatic translations
 */
class EntityExtractor implements ExtractorInterface
{
    protected $backendConfiguration;
    protected $configurator;
    protected $domain;

    /**
     * Constructor
     *
     * @param array        $backendConfiguration
     * @param Configurator $configurator
     * @param string       $domain
     */
    public function __construct($backendConfiguration, Configurator $configurator, $domain)
    {
        $this->backendConfiguration = $backendConfiguration;
        $this->configurator = $configurator;
        $this->domain = $domain;
    }

    /**
     * Extract translations
     *
     * @return MessageCatalogue
     */
    public function extract()
    {
        $catalogue = new MessageCatalogue();

        $automaticTranslation = $this->backendConfiguration['automatic_translation'];

        //we extract only if the automatic translations
        if ($automaticTranslation) {
            $catalogue = $this->getTranslations();
        }

        return $catalogue;
    }

    /**
     * Get the translations
     *
     * @return MessageCatalogue
     */
    protected function getTranslations()
    {
        $catalogue = new MessageCatalogue();
        $labels = array();

        $entities = $this->getEntities();

        foreach ($entities as $entity) {
            $entityConfiguration = $this->configurator->getEntityConfiguration($entity);
            $entityLabels = $this->getAllFieldsLabels($entityConfiguration);
            $labels = array_merge($labels, $entityLabels);
        }

        //avoid doublons
        $uniqueLabels = array_unique($labels);

        foreach ($uniqueLabels as $uniqueLabel) {
            $message = new Message($uniqueLabel, $this->domain);
            $catalogue->add($message);
        }

        return $catalogue;
    }

    /**
     * Get the list of entities
     *
     * @return string[] The list of entities
     */
    protected function getEntities()
    {
        $entities = array_keys($this->backendConfiguration['entities']);

        return $entities;
    }

    /**
     * Get all fields label of all views
     *
     * @param string[] $entityConfiguration
     *
     */
    protected function getAllFieldsLabels($entityConfiguration)
    {
        $labels = array();

        $views = array('edit', 'list', 'new', 'search', 'show');

        foreach ($views as $view) {
            $viewLabels = $this->getViewFieldLabels($entityConfiguration, $view);
            $labels = array_merge($labels, $viewLabels);
        }

        $uniqueLabels = array_unique($labels);

        return $uniqueLabels;
    }

    /**
     *
     * @param string[] $entityConfiguration
     * @param string $view
     */
    protected function getViewFieldLabels($entityConfiguration, $view)
    {
        $labels = array();

        //the view might have not been enabled
        if (isset($entityConfiguration[$view])) {
            $viewConfiguration = $entityConfiguration[$view];
            $fieldsConfiguration = $viewConfiguration['fields'];

            foreach ($fieldsConfiguration as $configuration) {
                $label = $configuration['label'];
                $labels[] = $label;
            }
        }

        return $labels;
    }
}
