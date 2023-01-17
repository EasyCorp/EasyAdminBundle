<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\ColumnStorage;

interface SelectedColumnStorageProviderInterface
{
    public function getSelectedColumns(string $key, array $defaultColumns, array $availableColumns): array;

    public function storeSelectedColumns(string $key, array $selectedColumns): bool;
}
