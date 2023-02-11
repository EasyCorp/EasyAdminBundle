<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class MenuItemMatcher implements MenuItemMatcherInterface
{
    public function __construct(private AdminContextProvider $adminContextProvider)
    {
    }

    public function isSelected(MenuItemDto $menuItemDto): bool
    {
        $adminContext = $this->adminContextProvider->getContext();
        if (null === $adminContext || $menuItemDto->isMenuSection()) {
            return false;
        }

        $currentPageQueryParameters = $adminContext->getRequest()->query->all();
        $menuItemQueryString = null === $menuItemDto->getLinkUrl() ? null : parse_url($menuItemDto->getLinkUrl(), \PHP_URL_QUERY);
        $menuItemQueryParameters = [];
        if (null !== $menuItemQueryString) {
            parse_str($menuItemQueryString, $menuItemQueryParameters);
        }

        if ([] === $menuItemQueryParameters || [] === $currentPageQueryParameters) {
            return $menuItemDto->getLinkUrl() === $adminContext->getRequest()->getUri();
        }

        $menuItemQueryParameters = $this->filterIrrelevantQueryParameters($menuItemQueryParameters);
        $currentPageQueryParameters = $this->filterIrrelevantQueryParameters($currentPageQueryParameters);

        // needed so you can pass route parameters in any order
        sort($menuItemQueryParameters);
        sort($currentPageQueryParameters);

        return $menuItemQueryParameters === $currentPageQueryParameters;
    }

    public function isExpanded(MenuItemDto $menuItemDto): bool
    {
        if ([] === $menuSubitems = $menuItemDto->getSubItems()) {
            return false;
        }

        foreach ($menuSubitems as $submenuItem) {
            if ($submenuItem->isSelected()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes from the given list of query parameters all the parameters that
     * should be ignored when deciding if some menu item matches the current page
     * (such as the applied filters or sorting, the listing page number, etc.).
     */
    private function filterIrrelevantQueryParameters(array $queryStringParameters): array
    {
        $paramsToRemove = [EA::REFERRER, EA::PAGE, EA::FILTERS, EA::SORT, EA::CRUD_ACTION];

        return array_filter($queryStringParameters, static fn ($k) => !\in_array($k, $paramsToRemove, true), \ARRAY_FILTER_USE_KEY);
    }
}
