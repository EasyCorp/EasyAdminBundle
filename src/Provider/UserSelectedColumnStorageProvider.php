<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\ColumnStorage\SelectedColumnStorageProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\ColumnStorage\UserParametersStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserSelectedColumnStorageProvider implements SelectedColumnStorageProviderInterface
{
    private TokenStorageInterface $tokenStorage;
    private ManagerRegistry $managerRegistry;

    public function __construct(TokenStorageInterface $tokenStorage, ManagerRegistry $managerRegistry)
    {
        $this->tokenStorage = $tokenStorage;
        $this->managerRegistry = $managerRegistry;
    }

    public function getSelectedColumns(string $key, array $defaultColumns, array $availableColumns): array
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return $defaultColumns;
        }
        $user = $token->getUser();
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
        if (null === $token = $this->tokenStorage->getToken()) {
            return false;
        }
        $user = $token->getUser();
        if (!\is_object($user) || !($user instanceof UserParametersStorageInterface)) {
            return false;
        }
        $em = $this->managerRegistry->getManagerForClass($user::class);
        if (!\is_object($em)) {
            return false;
        }
        $em->persist($user->setOrAddParameter($key, $selectedColumns));
        $em->flush();

        return true;
    }
}
