<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Translation;

use Symfony\Component\Translation\MessageCatalogue;

use Symfony\Component\Translation\Extractor\ExtractorInterface;

/**
 * The extractor for the automatic translations
 *
 * ref: easyadmin.translation.entity_extractor
 */
class EntityExtractor implements ExtractorInterface
{
    protected $domain;
    protected $entityExtractor;
    protected $prefix;


    /**
     * Constructor
     *
     * @param EntityExtractor $entityExtractor
     * @param string          $domain
     */
    public function __construct(EntityTranslation $entityExtractor, $domain)
    {
        $this->entityExtractor = $entityExtractor;
        $this->domain = $domain;
    }

    /**
     * Set the prefix
     *
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Extract translations
     *
     * @param type             $directory
     * @param MessageCatalogue $catalogue
     *
     * @return MessageCatalogue
     */
    public function extract($directory, MessageCatalogue $catalogue)
    {
        $this->addTranslations($catalogue);

        return $catalogue;
    }

    /**
     * Get the translations
     *
     * @return MessageCatalogue
     */
    protected function addTranslations(MessageCatalogue $catalogue)
    {
        $labels = $this->entityExtractor->getTranslations();

        $messages = array();

        foreach ($labels as $label) {
            $messages[$label] = $label;
        }

        $catalogue->add($messages, $this->domain);

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
