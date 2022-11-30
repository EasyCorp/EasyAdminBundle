<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Interfaces\SelectedColumnStorageProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Interfaces\UserParametersStorageInterface;
use Symfony\Component\Security\Core\Security;

class UserSelectedColumnStorageProvider implements SelectedColumnStorageProviderInterface
{
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
        if (!\is_object($user) || !($user instanceof UserParametersStorageInterface)) {
            return $defaultColumns;
        }
        $columns = array_values($user->getParameter($key));
        if (\count($columns) < 1) {
            $columns = array_values($defaultColumns);
        }

        return array_unique(array_filter(array_intersect($columns, array_values($availableColumns))));
    }

    public function storeSelectedColumns(string $key, array $selectedColumns): bool
    {
        $user = $this->security->getUser();
        if (!\is_object($user) || !($user instanceof UserParametersStorageInterface)) {
            return false;
        }
        $em = $this->managerRegistry->getManagerForClass(\get_class($user));
        if (!\is_object($em)) {
            return false;
        }
        $em->persist($user->setOrAddParameter($key, $selectedColumns));
        $em->flush();

        return true;
    }
}
