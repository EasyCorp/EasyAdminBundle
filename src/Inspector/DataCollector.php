<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Inspector;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector as BaseDataCollector;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * Collects information about the requests related to EasyAdmin and displays
 * it both in the web debug toolbar and in the profiler.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class DataCollector extends BaseDataCollector
{
    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
    ) {
    }

    public function reset(): void
    {
        $this->data = [];
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        if (null === $context = $this->adminContextProvider->getContext()) {
            return;
        }

        $collectedData = [];
        foreach ($this->collectData($context) as $key => $value) {
            $collectedData[$key] = $this->cloneVar($value);
        }

        $this->data = $collectedData;
    }

    public function isEasyAdminRequest(): bool
    {
        return 0 !== \count($this->data);
    }

    /**
     * @return array<mixed>|Data
     */
    public function getData(): array|Data
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectData(AdminContextInterface $context): array
    {
        $attributes = $context->getRequest()->attributes->all();
        $query = $context->getRequest()->query->all();

        return [
            'CRUD Controller FQCN' => null === $context->getCrud() ? null : $context->getCrud()->getControllerFqcn(),
            'CRUD Action' => $attributes[EA::CRUD_ACTION] ?? $query[EA::CRUD_ACTION] ?? null,
            'Entity ID' => $attributes[EA::ENTITY_ID] ?? $query[EA::ENTITY_ID] ?? null,
            'Sort' => $attributes[EA::SORT] ?? $query[EA::SORT] ?? null,
        ];
    }

    public function getName(): string
    {
        return 'easyadmin';
    }
}
