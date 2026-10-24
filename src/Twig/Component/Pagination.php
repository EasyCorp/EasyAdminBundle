<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityPaginatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Radius;
use EasyCorp\Bundle\EasyAdminBundle\Util\PageRangeCalculator;

/**
 * Renders a pagination control with links to navigate the pages of some results,
 * and an optional counter of the total number of results.
 * Inspired by https://ui.shadcn.com/docs/components/pagination.
 *
 * It supports two exclusive modes:
 *   * object mode: pass an EntityPaginatorInterface via the "paginator" prop
 *     (this is how EasyAdmin renders the pagination of CRUD index pages);
 *   * raw mode: pass the "currentPage" and "urlPattern" props plus either
 *     "lastPage" or "totalItems" + "pageSize" (usable on any page, even
 *     outside EasyAdmin backends).
 */
final class Pagination
{
    /**
     * The paginator of EasyAdmin index pages; when passed, you cannot pass currentPage, lastPage, totalItems, pageSize or urlPattern.
     */
    public ?EntityPaginatorInterface $paginator = null;
    /**
     * The number of the current page (required when not passing a paginator).
     */
    public ?int $currentPage = null;
    /**
     * The number of the last page (or pass totalItems and pageSize instead).
     */
    public ?int $lastPage = null;
    /**
     * The total number of items, used with pageSize to calculate the last page.
     */
    public ?int $totalItems = null;
    /**
     * The number of items per page, used with totalItems to calculate the last page.
     */
    public ?int $pageSize = null;
    /**
     * The URL of the page links, with a '{page}' placeholder (required when not passing a paginator).
     */
    public ?string $urlPattern = null;
    /**
     * The size of the control: 'sm' or 'md'.
     */
    public string $size = 'md';
    /**
     * The border radius of the page links: 'none', 'sm', 'md', 'lg' or 'full'; when null, they inherit the border radius of the project.
     */
    public ?Radius $radius = null;
    /**
     * Whether to display the total number of results.
     */
    public bool $showResultsCount = true;
    /**
     * Whether to display the links to each page number.
     */
    public bool $showPageNumbers = true;
    /**
     * Whether to display the links to the first and last pages.
     */
    public bool $showFirstLast = false;
    /**
     * Whether to display the labels of the previous/next links (otherwise only their icons are visible).
     */
    public bool $showPreviousNextLabels = true;
    /**
     * How many page links are displayed around the current page (default: 3).
     */
    public ?int $rangeSize = null;
    /**
     * How many page links are displayed at the first and last pages (default: 1).
     */
    public ?int $rangeEdgeSize = null;
    /**
     * The translation domain used to translate the labels of the control.
     */
    public string $translationDomain = 'EasyAdminBundle';

    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
    ) {
    }

    public function mount(?EntityPaginatorInterface $paginator = null, ?int $currentPage = null, ?int $lastPage = null, ?int $totalItems = null, ?int $pageSize = null, ?string $urlPattern = null, string|Radius|null $radius = null): void
    {
        if (null !== $paginator && (null !== $currentPage || null !== $lastPage || null !== $totalItems || null !== $pageSize || null !== $urlPattern)) {
            throw new \InvalidArgumentException('When passing a "paginator" object to the <twig:ea:Pagination> component, you cannot pass any of the "currentPage", "lastPage", "totalItems", "pageSize" or "urlPattern" props.');
        }

        if (null === $paginator) {
            if (null === $currentPage || null === $urlPattern) {
                throw new \InvalidArgumentException('When not passing a "paginator" object to the <twig:ea:Pagination> component, you must pass both the "currentPage" and "urlPattern" props.');
            }

            if (!str_contains($urlPattern, '{page}')) {
                throw new \InvalidArgumentException(sprintf('The "urlPattern" prop of the <twig:ea:Pagination> component must contain the "{page}" placeholder (e.g. "?page={page}"), but "%s" was given.', $urlPattern));
            }

            if (null !== $lastPage && (null !== $totalItems || null !== $pageSize)) {
                throw new \InvalidArgumentException('In the <twig:ea:Pagination> component, pass either the "lastPage" prop or the "totalItems" and "pageSize" props, but not both.');
            }

            if (null === $lastPage && (null === $totalItems || null === $pageSize)) {
                throw new \InvalidArgumentException('When not passing a "paginator" object to the <twig:ea:Pagination> component, you must pass either the "lastPage" prop or both the "totalItems" and "pageSize" props.');
            }

            if (null !== $pageSize && $pageSize < 1) {
                throw new \InvalidArgumentException(sprintf('The "pageSize" prop of the <twig:ea:Pagination> component must be 1 or higher, but %d was given.', $pageSize));
            }
        }

        $this->paginator = $paginator;
        $this->currentPage = null === $currentPage ? null : max(1, $currentPage);
        $this->lastPage = null !== $lastPage ? max(1, $lastPage) : (null !== $totalItems && null !== $pageSize ? max(1, (int) ceil($totalItems / $pageSize)) : null);
        $this->totalItems = $totalItems;
        $this->pageSize = $pageSize;
        $this->urlPattern = $urlPattern;

        if ($radius instanceof Radius || null === $radius) {
            $this->radius = $radius;
        } else {
            $this->radius = Radius::tryFrom($radius);
        }
    }

    public function getCurrentPage(): int
    {
        return null !== $this->paginator ? $this->paginator->getCurrentPage() : ($this->currentPage ?? 1);
    }

    public function getLastPage(): int
    {
        return null !== $this->paginator ? $this->paginator->getLastPage() : ($this->lastPage ?? 1);
    }

    public function hasPreviousPage(): bool
    {
        return null !== $this->paginator ? $this->paginator->hasPreviousPage() : $this->getCurrentPage() > 1;
    }

    public function getPreviousPage(): int
    {
        return null !== $this->paginator ? $this->paginator->getPreviousPage() : max(1, $this->getCurrentPage() - 1);
    }

    public function hasNextPage(): bool
    {
        return null !== $this->paginator ? $this->paginator->hasNextPage() : $this->getCurrentPage() < $this->getLastPage();
    }

    public function getNextPage(): int
    {
        return null !== $this->paginator ? $this->paginator->getNextPage() : min($this->getLastPage(), $this->getCurrentPage() + 1);
    }

    // returns NULL when the total number of results is unknown
    // (raw mode with only a "lastPage" prop); the counter is hidden in that case
    public function getNumResults(): ?int
    {
        return null !== $this->paginator ? $this->paginator->getNumResults() : $this->totalItems;
    }

    /**
     * @return iterable<int|null> NULL values mean a gap in the pagination (rendered as an ellipsis)
     */
    public function getPageRange(): iterable
    {
        if (null !== $this->paginator) {
            return $this->paginator->getPageRange($this->rangeSize, $this->rangeEdgeSize);
        }

        return PageRangeCalculator::calculate($this->getCurrentPage(), $this->getLastPage(), $this->rangeSize ?? 3, $this->rangeEdgeSize ?? 1);
    }

    public function getUrlForPage(int $page): string
    {
        return null !== $this->paginator ? $this->paginator->generateUrlForPage($page) : str_replace('{page}', (string) $page, $this->urlPattern ?? '');
    }

    public function isRtl(): bool
    {
        // in raw mode outside an EasyAdmin backend there's no admin context, so this falls back to LTR
        return 'rtl' === $this->adminContextProvider->getContext()?->getI18n()->getTextDirection();
    }

    public function getDefaultCssClass(): string
    {
        return 'list-pagination';
    }

    public function getNavCssClass(): string
    {
        $cssClasses = ['pager', 'list-pagination-paginator'];

        if (!$this->hasPreviousPage()) {
            $cssClasses[] = 'first-page';
        }
        if (!$this->hasNextPage()) {
            $cssClasses[] = 'last-page';
        }

        return implode(' ', $cssClasses);
    }

    public function getListCssClass(): string
    {
        return 'pagination'.('sm' === $this->size ? ' pagination-sm' : '');
    }

    public function getPageLinkCssClass(): string
    {
        // when no radius is set, the page links inherit the project's default border-radius
        return 'page-link'.(null !== $this->radius ? ' '.$this->radius->cssClass() : '');
    }
}
