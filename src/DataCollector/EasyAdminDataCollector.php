<?php

namespace EasyCorp\Bundle\EasyAdminBundle\DataCollector;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\EntityIdReader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector as BaseDataCollector;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * Collects information about the requests related to EasyAdmin and displays
 * it both in the web debug toolbar and in the profiler.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EasyAdminDataCollector extends BaseDataCollector
{
    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
        private readonly ?UrlGeneratorInterface $urlGenerator = null,
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

        $this->data = $this->collectData($context);
    }

    public function isEasyAdminRequest(): bool
    {
        return [] !== $this->data;
    }

    /**
     * @return array<mixed>|Data
     */
    public function getData(): array|Data
    {
        return $this->data;
    }

    public function getName(): string
    {
        return 'easyadmin';
    }

    /**
     * @return array<string, mixed>
     */
    private function collectData(AdminContextInterface $context): array
    {
        $request = $context->getRequest();
        $crud = $context->getCrud();
        $adminRouteControllerFqcn = $request->attributes->get(EA::CRUD_CONTROLLER_FQCN);
        // the values are read with all() because get() throws when they aren't scalar (e.g. ?page[]=1)
        // and the collector runs even for the error response of that request
        $query = $request->query->all();

        return [
            'dashboard' => [
                'title' => $context->getDashboardTitle(),
                'controller_fqcn' => $context->getDashboardControllerFqcn(),
                'route_name' => $context->getDashboardRouteName(),
                'locale' => $context->getI18n()->getLocale(),
                'text_direction' => $context->getI18n()->getTextDirection(),
                'translation_domain' => $context->getI18n()->getTranslationDomain(),
                'icon_set' => $context->getAssets()->getIconSet(),
                'theme' => null === $context->getDashboardTheme() ? null : $this->cloneVar($context->getDashboardTheme()->getCssVariables()),
            ],
            'route' => [
                'name' => $request->attributes->get('_route'),
                'path' => $request->getPathInfo(),
                'controller' => $this->cloneVar($request->attributes->get('_controller')),
            ],
            'crud' => null === $crud ? null : [
                'controller_fqcn' => $crud->getControllerFqcn(),
                'entity_fqcn' => $crud->getEntityFqcn(),
                'action' => $crud->getCurrentAction(),
                'page' => $crud->getCurrentPage(),
                'entity_id' => EntityIdReader::fromRequest($request),
            ],
            // custom admin routes (#[AdminRoute] in controllers that aren't CRUD controllers)
            // store their controller and method in the same route attributes as CRUD routes
            'admin_route' => null !== $crud || null === $adminRouteControllerFqcn ? null : [
                'controller_fqcn' => $adminRouteControllerFqcn,
                'method' => $request->attributes->get(EA::CRUD_ACTION),
            ],
            // the list state is read from the query string on purpose: it's not part of the route
            'list_state' => Crud::PAGE_INDEX !== $crud?->getCurrentPage() ? null : [
                'sort' => $this->cloneVar($query[EA::SORT] ?? null),
                'filters' => $this->cloneVar($query[EA::FILTERS] ?? null),
                'query' => $this->cloneVar($query[EA::QUERY] ?? null),
                'page' => $this->cloneVar($query[EA::PAGE] ?? null),
            ],
            'ui_kit_url' => $this->generateUiKitUrl($context),
        ];
    }

    private function generateUiKitUrl(AdminContextInterface $context): ?string
    {
        if (null === $this->urlGenerator) {
            return null;
        }

        try {
            return $this->urlGenerator->generate($context->getDashboardRouteName().'_ea_ui_kit_index');
        } catch (RoutingExceptionInterface) {
            // the UI Kit routes don't exist when it's disabled (by default, outside debug mode) and
            // they can't be generated when the dashboard route requires parameters; in both cases
            // the profiler panel doesn't show the link instead of failing to collect the request data
            return null;
        }
    }
}
