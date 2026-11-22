<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Environment;

/**
 * Renders the contents of widgets: it computes their data, renders their
 * templates, caches the result and turns failures into an error message.
 *
 * @internal
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class WidgetRenderer
{
    /**
     * The suffix added to the route name of a dashboard to get the name of
     * the route that serves the contents of its widgets.
     */
    public const ROUTE_NAME_SUFFIX = '_widget';
    /**
     * The name of the route placeholder that holds the name of the widget.
     */
    public const ROUTE_PARAMETER = 'widgetName';
    /**
     * The HTTP header that identifies the responses with the contents of a widget.
     * The JavaScript code that loads widgets ignores the responses without it
     * (e.g. the login page returned after the session expired).
     */
    public const RESPONSE_HEADER = 'X-EasyAdmin-Widget';
    public const ERROR_TEMPLATE = '@EasyAdmin/widget/_error.html.twig';

    /** @var \WeakMap<Request, array<string, mixed>> */
    private \WeakMap $contextAttributesOfRequest;
    /** @var \WeakMap<Request, true> */
    private \WeakMap $requestsWithScript;
    /** @var \WeakMap<WidgetDto, true> */
    private \WeakMap $failedWidgets;

    /**
     * @param iterable<WidgetContextProviderInterface> $contextProviders
     * @param iterable<WidgetConfiguratorInterface>    $configurators
     */
    public function __construct(
        private readonly Environment $twig,
        private readonly AdminContextProviderInterface $adminContextProvider,
        private readonly iterable $contextProviders,
        private readonly CacheInterface $cache,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ?LoggerInterface $logger = null,
        private readonly bool $debug = false,
        private readonly iterable $configurators = [],
    ) {
        $this->contextAttributesOfRequest = new \WeakMap();
        $this->requestsWithScript = new \WeakMap();
        $this->failedWidgets = new \WeakMap();
    }

    /**
     * Returns the HTML contents of the widget (without the card that surrounds
     * them). If the widget fails, in debug mode the exception is thrown; otherwise
     * it's logged and the returned contents are an error message, so the rest
     * of the widgets are still displayed.
     */
    public function renderBody(WidgetDto $widget): string
    {
        $adminContext = $this->getAdminContext();
        unset($this->failedWidgets[$widget]);

        try {
            // configurators only run when the contents are computed (not on cache hits or for the placeholders
            // of lazy widgets), so they change a copy: the rest of the page never depends on what they did
            $configuredWidget = clone $widget;
            // the context providers run application code, so their failures are handled like the failures of the widget
            $widgetContext = $this->createContext($configuredWidget);

            if (null === $cacheTtl = $widget->getCacheTtl()) {
                return $this->doRenderBody($configuredWidget, $widgetContext, $adminContext);
            }

            // exceptions thrown inside the callback are not cached, so failed widgets are computed again in the next request
            return $this->cache->get($this->getCacheKey($widget, $adminContext), function (ItemInterface $item) use ($configuredWidget, $widgetContext, $adminContext, $cacheTtl): string {
                $item->expiresAfter($cacheTtl);

                return $this->doRenderBody($configuredWidget, $widgetContext, $adminContext);
            });
        } catch (\Throwable $e) {
            if ($this->debug) {
                throw $e;
            }

            $this->failedWidgets[$widget] = true;
            $this->logger?->error('The "{widget}" widget of the "{dashboard}" dashboard failed to render: {message}', [
                'widget' => $widget->getName(),
                'dashboard' => $adminContext->getDashboardControllerFqcn(),
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return $this->renderError($widget);
        }
    }

    /**
     * Returns the error message displayed instead of the contents of a widget that can't be rendered or loaded.
     */
    public function renderError(WidgetDto $widget): string
    {
        return $this->twig->render(self::ERROR_TEMPLATE, ['widget' => $widget, 'is_retryable' => null !== $this->getFragmentUrl($widget)]);
    }

    /**
     * Whether the last call to renderBody() for this widget returned an error message.
     */
    public function hasFailed(WidgetDto $widget): bool
    {
        return isset($this->failedWidgets[$widget]);
    }

    public function createContext(WidgetDto $widget): WidgetContext
    {
        $adminContext = $this->getAdminContext();
        $request = $adminContext->getRequest();

        return new WidgetContext(
            $request,
            $adminContext->getUser(),
            $adminContext->getDashboardControllerFqcn(),
            $widget,
            $this->getContextAttributes($request),
        );
    }

    /**
     * Returns the URL that serves the contents of the widget, which keeps the query string
     * of the current request so the widget sees the same parameters as the page that
     * displays it. It's null when the dashboard has no route to serve its widgets.
     */
    public function getFragmentUrl(WidgetDto $widget): ?string
    {
        $adminContext = $this->getAdminContext();

        $request = $adminContext->getRequest();
        $parameters = $request->query->all();
        // these parameters make EasyAdmin run a different controller for the request
        unset($parameters[EA::ROUTE_NAME], $parameters[EA::ROUTE_PARAMS]);
        // the parameters of the current route are needed when the dashboard path has placeholders (e.g. '/{tenant}/admin');
        // they are added last so a query parameter with the same name can't replace them
        $parameters = array_replace($parameters, $this->getScalarRouteParameters($request));
        $parameters[self::ROUTE_PARAMETER] = (string) $widget->getName();

        try {
            return $this->urlGenerator->generate($adminContext->getDashboardRouteName().self::ROUTE_NAME_SUFFIX, $parameters);
        } catch (RoutingExceptionInterface) {
            return null;
        }
    }

    /**
     * Returns true only the first time it's called in each request, so the
     * JavaScript code that loads widgets is included once per page.
     */
    public function shouldIncludeScript(): bool
    {
        $request = $this->getAdminContext()->getRequest();
        if (isset($this->requestsWithScript[$request])) {
            return false;
        }

        $this->requestsWithScript[$request] = true;

        return true;
    }

    private function doRenderBody(WidgetDto $widget, WidgetContext $widgetContext, AdminContextInterface $adminContext): string
    {
        foreach ($this->configurators as $configurator) {
            if ($configurator->supports($widget)) {
                $configurator->configure($widget, $widgetContext);
            }
        }

        $templatePath = $widget->getTemplatePath();
        if (null === $templatePath && null !== $templateName = $widget->getTemplateName()) {
            $templatePath = $adminContext->getTemplatePath($templateName);
        }

        if (null === $templatePath) {
            throw new \LogicException(sprintf('The "%s" widget (of type "%s") doesn\'t define the template used to render its contents. Call the "setTemplatePath()" method on it.', (string) $widget->getName(), (string) $widget->getWidgetFqcn()));
        }

        $data = [];
        foreach ($widget->getData() as $name => $valueOrCallable) {
            $data[$name] = $widgetContext->resolve($valueOrCallable);
        }

        return $this->twig->render($templatePath, [
            'widget' => $widget,
            'data' => $data,
            'context' => $widgetContext,
        ]);
    }

    private function getCacheKey(WidgetDto $widget, AdminContextInterface $adminContext): string
    {
        $request = $adminContext->getRequest();

        // the same dashboard can serve different contents on each host or route placeholder (e.g. '/{tenant}/admin')
        $routeParameters = $this->getScalarRouteParameters($request);
        ksort($routeParameters);

        $user = $adminContext->getUser();
        $userKey = match (true) {
            $widget->isCacheShared() => 'shared',
            null === $user => 'anonymous',
            // roles are part of the key so the contents that depend on them don't outlive a change of roles
            default => 'user:'.$user->getUserIdentifier().':'.implode(',', $user->getRoles()),
        };

        $keyParts = [
            EasyAdminBundle::VERSION,
            $adminContext->getDashboardControllerFqcn(),
            (string) $widget->getName(),
            $request->getLocale(),
            $request->getHost(),
            hash('xxh128', serialize($routeParameters)),
            $this->getContextAttributesHash($request),
            $userKey,
        ];

        // hashing also removes the characters that cache keys can't contain
        return 'ea_widget_'.hash('xxh128', implode("\0", $keyParts));
    }

    /**
     * @return array<string, mixed>
     */
    private function getContextAttributes(Request $request): array
    {
        if (isset($this->contextAttributesOfRequest[$request])) {
            return $this->contextAttributesOfRequest[$request];
        }

        $attributes = [];
        foreach ($this->contextProviders as $contextProvider) {
            $attributes = array_merge($attributes, $contextProvider->getAttributes($request));
        }

        return $this->contextAttributesOfRequest[$request] = $attributes;
    }

    private function getContextAttributesHash(Request $request): string
    {
        try {
            return hash('xxh128', serialize($this->getContextAttributes($request)));
        } catch (\Throwable $e) {
            throw new \LogicException(sprintf('The attributes returned by the widget context providers are part of the cache key of widgets, so they must be serializable: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * @return array<string, scalar>
     */
    private function getScalarRouteParameters(Request $request): array
    {
        $routeParameters = array_filter((array) $request->attributes->get('_route_params', []), static fn (mixed $value): bool => \is_scalar($value));
        // the page and the requests that load its widgets must get the same parameters
        unset($routeParameters[self::ROUTE_PARAMETER]);

        return $routeParameters;
    }

    private function getAdminContext(): AdminContextInterface
    {
        return $this->adminContextProvider->getContext() ?? throw new \LogicException('Widgets can only be rendered in the pages served by EasyAdmin (the current request has no admin context).');
    }
}
