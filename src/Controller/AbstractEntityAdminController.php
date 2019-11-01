<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\EntityAdminConfig;
use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
abstract class AbstractEntityAdminController extends AbstractController implements EntityAdminControllerInterface
{
    protected $processedConfig;

    abstract public function getEntityAdminConfig(): EntityAdminConfig;

    abstract public function getFields(string $action): iterable;

    public function getEntityClass(): string
    {
        return $this->getProcessedConfig()->get('entityFqcn');
    }

    public function getNameInSingular(): string
    {
        return $this->getProcessedConfig()->get('labelInSingular');
    }

    public function getNameInPlural(): string
    {
        return $this->getProcessedConfig()->get('labelInPlural');
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'ea.authorization_checker' => '?'.AuthorizationChecker::class,
        ]);
    }

    public function index(): Response
    {
        return new Response('TODO');
    }

    protected function getProcessedConfig(): EntityAdminConfig
    {
        if (null !== $this->processedConfig) {
            return $this->getProcessedConfig();
        }

        return $this->processedConfig = $this->getEntityAdminConfig();
    }
}
