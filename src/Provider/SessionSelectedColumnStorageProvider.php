<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\ColumnStorage\SelectedColumnStorageProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionSelectedColumnStorageProvider implements SelectedColumnStorageProviderInterface
{
    public const KEY_PREFIX = 'column.chooser.';

    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getKeyName($key): string
    {
        return self::KEY_PREFIX.$key;
    }

    public function getSelectedColumns(string $key, array $defaultColumns, array $availableColumns): array
    {
        $columns = $this->requestStack->getSession()->get($this->getKeyName($key), []);
        if (!\is_array($columns) || \count($columns) < 1) {
            $columns = $defaultColumns;
        }

        return array_unique(array_filter(array_intersect($columns, $availableColumns)));
    }

    public function storeSelectedColumns(string $key, array $selectedColumns): bool
    {
        $this->requestStack->getSession()->set($this->getKeyName($key), $selectedColumns);

        return true;
    }
}
