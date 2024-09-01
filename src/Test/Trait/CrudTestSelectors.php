<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Test\Trait;

trait CrudTestSelectors
{
    protected function getIndexEntityActionSelector(string $action, string|int $entityId): string
    {
        return $this->getIndexEntityRowSelector($entityId).' '.$this->getActionSelector($action);
    }

    protected function getIndexEntityRowSelector(string|int $entityId): string
    {
        return sprintf('tbody tr[data-id="%s"]', (string) $entityId);
    }

    protected function getActionSelector(string $action): string
    {
        return sprintf('.action-%s', $action);
    }

    protected function getGlobalActionSelector(string $action): string
    {
        return '.global-actions '.$this->getActionSelector($action);
    }

    protected function getIndexHeaderColumnSelector(string $columnName): string
    {
        return $this->getIndexHeaderRowSelector().' '.$this->getIndexColumnSelector($columnName, 'header');
    }

    protected function getIndexHeaderRowSelector(): string
    {
        return 'thead tr';
    }

    protected function getIndexColumnSelector(string $columnName, string $type = 'header'): string
    {
        $columnSelector = match ($type) {
            'header' => 'th',
            'data' => 'td',
            default => 'th',
        };

        return sprintf('%s[data-column="%s"]', $columnSelector, $columnName);
    }

    protected function getEntityFormSelector(): string
    {
        return 'form[method="post"]';
    }

    protected function getFormEntity(): string
    {
        $form = $this->client->getCrawler()->filter($this->getEntityFormSelector());

        return $form->attr('name');
    }

    protected function getFormFieldIdValue(string $fieldName): string
    {
        return sprintf('%s_%s', $this->getFormEntity(), $fieldName);
    }

    protected function getFormFieldSelector(string $fieldName): string
    {
        return sprintf('%s #%s', $this->getEntityFormSelector(), $this->getFormFieldIdValue($fieldName));
    }

    protected function getFormFieldLabelSelector(string $fieldName): string
    {
        return sprintf('%s label[for="%s"]', $this->getEntityFormSelector(), $this->getFormFieldIdValue($fieldName));
    }
}
