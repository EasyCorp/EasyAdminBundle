<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Inspector;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector as BaseDataCollector;

/**
 * Collects information about the requests related to EasyAdmin and displays
 * it both in the web debug toolbar and in the profiler.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class DataCollector extends BaseDataCollector
{
    private $applicationContextProvider;

    public function __construct(ApplicationContextProvider $applicationContextProvider)
    {
        $this->applicationContextProvider = $applicationContextProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [];
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, $exception = null)
    {
        if (null === $context = $this->applicationContextProvider->getContext()) {
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
        return !empty($this->data);
    }

    public function getData(): array
    {
        return $this->data;
    }

    private function collectData(ApplicationContext $context): array
    {
        return [
            'CRUD Controller' => $context->getRequest()->get('crudController') ?? null,
            'CRUD Action' => $context->getRequest()->get('crudAction') ?? null,
            'Entity Id' => $context->getRequest()->get('entityId') ?? null,
            'Sort' => $context->getRequest()->get('sort') ?? null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'easyadmin';
    }
}
