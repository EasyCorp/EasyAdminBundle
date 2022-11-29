<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Interfaces\SelectedColumnStorageProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Interfaces\UserParametersStorageInterface;
use Symfony\Component\Security\Core\Security;
use Doctrine\Persistence\ManagerRegistry;

class UserSelectedColumnStorageProvider implements SelectedColumnStorageProviderInterface {

    private Security $security;
    private ManagerRegistry $managerRegistry;
    
    public function __construct(Security $security, ManagerRegistry $managerRegistry)
    {
        $this->security = $security;
        $this->managerRegistry = $managerRegistry;
    }

    public function getSelectedColumns(string $key, array $defaultColumns, array $availableColumns): array
    {
        $user = $this->security->getUser();
        if (! is_object($user) || ! ($user instanceof UserParametersStorageInterface)) {
            return $defaultColumns;
        }
        $columns = $user->getParameter($key);
        if (! is_array($columns) || count($columns) < 1) {
            $columns = $defaultColumns;
        }
        return array_unique(array_filter(array_intersect($columns, $availableColumns)));
    }

    public function storeSelectedColumns(string $key, array $selectedColumns): bool
    {
        try {
            $user = $this->security->getUser();
            if (! is_object($user) || ! ($user instanceof UserParametersStorageInterface)) {
                return false;
            }
            $em = $this->managerRegistry->getManagerForClass(get_class($user));
            if (! is_object($em)) {
                return false;
            }
            $em->persist($user->setOrAddParameter($key, $selectedColumns));
            $em->flush();
        }
        catch(\Throwable $e)
        {
            return false;
        }
        return true;
    }
}