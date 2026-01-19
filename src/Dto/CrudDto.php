<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SearchMode;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Translation\TranslatableMessageBuilder;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Contracts\Translation\TranslatableInterface;
use function Symfony\Component\Translation\t;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CrudDto
{
    /** @var class-string<CrudControllerInterface>|null */
    private ?string $controllerFqcn = null;
    private AssetsDto $fieldAssetsDto;
    private ?string $pageName = null;
    private ?string $actionName = null;
    private ?ActionConfigDto $actionConfigDto = null;
    private ?FilterConfigDto $filters = null;
    /** @var class-string|null */
    private ?string $entityFqcn = null;
    /** @var TranslatableInterface|string|callable|null */
    private $entityLabelInSingular;
    /** @var TranslatableInterface|string|callable|null */
    private $entityLabelInPlural;
    /** @var array<Crud::PAGE_*, string> */
    private array $defaultPageTitles = [
        Crud::PAGE_DETAIL => 'page_title.detail',
        Crud::PAGE_EDIT => 'page_title.edit',
        Crud::PAGE_INDEX => 'page_title.index',
        Crud::PAGE_NEW => 'page_title.new',
    ];
    /** @var array<string, TranslatableInterface|string|callable|null> */
    private array $customPageTitles = [
        Crud::PAGE_DETAIL => null,
        Crud::PAGE_EDIT => null,
        Crud::PAGE_INDEX => null,
        Crud::PAGE_NEW => null,
    ];
    /** @var array<string, string|TranslatableInterface|null> */
    private array $helpMessages = [
        Crud::PAGE_DETAIL => null,
        Crud::PAGE_EDIT => null,
        Crud::PAGE_INDEX => null,
        Crud::PAGE_NEW => null,
    ];
    private ?string $datePattern = 'medium';
    private ?string $timePattern = 'medium';
    /** @var array{string, string} */
    private array $dateTimePattern = ['medium', 'medium'];
    private string $dateIntervalFormat = '%%y Year(s) %%m Month(s) %%d Day(s)';
    private ?string $timezone = null;
    private ?string $numberFormat = null;
    private ?string $thousandsSeparator = null;
    private ?string $decimalSeparator = null;
    /** @var array<string, 'ASC'|'DESC'> */
    private array $defaultSort = [];
    /** @var array<string>|null */
    private ?array $searchFields = [];
    private string $searchMode = SearchMode::ALL_TERMS;
    private bool $autofocusSearch = false;
    private bool $showEntityActionsAsDropdown = true;
    private ?PaginatorDto $paginatorDto = null;
    /** @var array<string, string> */
    private array $overriddenTemplates;
    /** @var array<string> */
    private array $formThemes = ['@EasyAdmin/crud/form_theme.html.twig'];
    private KeyValueStore $newFormOptions;
    private KeyValueStore $editFormOptions;
    private string|Expression|null $entityPermission = null;
    private ?string $contentWidth = null;
    private ?string $sidebarWidth = null;
    private bool $hideNullValues = false;
    private bool|string|TranslatableInterface $askConfirmationOnBatchActions = true;

    public function __construct()
    {
        $this->fieldAssetsDto = new AssetsDto();
        $this->newFormOptions = KeyValueStore::new();
        $this->editFormOptions = KeyValueStore::new();
        $this->overriddenTemplates = [];
    }

    /**
     * @return class-string<CrudControllerInterface>|null
     */
    public function getControllerFqcn(): ?string
    {
        return $this->controllerFqcn;
    }

    /**
     * @param class-string<CrudControllerInterface> $fqcn
     */
    public function setControllerFqcn(string $fqcn): void
    {
        $this->controllerFqcn = $fqcn;
    }

    public function getCurrentPage(): ?string
    {
        return $this->pageName;
    }

    public function setPageName(?string $pageName): void
    {
        $this->pageName = $pageName;
    }

    public function getFieldAssets(string $pageName): AssetsDto
    {
        return $this->fieldAssetsDto;
    }

    public function setFieldAssets(AssetsDto $assets): void
    {
        $this->fieldAssetsDto = $assets;
    }

    /**
     * @return class-string
     */
    public function getEntityFqcn(): string
    {
        return $this->entityFqcn;
    }

    /**
     * @param class-string $entityFqcn
     */
    public function setEntityFqcn(string $entityFqcn): void
    {
        $this->entityFqcn = $entityFqcn;
    }

    /**
     * @param object|null $entityInstance
     * @param string|null $pageName
     */
    public function getEntityLabelInSingular(/* ?object */ $entityInstance = null, /* ?string */ $pageName = null): TranslatableInterface|string|null
    {
        if (null === $this->entityLabelInSingular) {
            return null;
        }

        if (
            \is_string($this->entityLabelInSingular)
            || $this->entityLabelInSingular instanceof TranslatableInterface
        ) {
            return $this->entityLabelInSingular;
        }

        return ($this->entityLabelInSingular)($entityInstance, $pageName);
    }

    /**
     * @param TranslatableInterface|string|callable $label
     */
    public function setEntityLabelInSingular($label): void
    {
        if (null !== $label && !\is_string($label) && !$label instanceof TranslatableInterface && !\is_callable($label)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.27.0',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$label',
                __METHOD__,
                '"string" or "TranslatableInterface" or "callable" or "null"',
                \gettype($label)
            );
        }
        $this->entityLabelInSingular = $label;
    }

    /**
     * @param object|null $entityInstance
     * @param string|null $pageName
     */
    public function getEntityLabelInPlural(/* ?object */ $entityInstance = null, /* ?string */ $pageName = null): TranslatableInterface|string|null
    {
        if (null === $this->entityLabelInPlural) {
            return null;
        }

        if (
            \is_string($this->entityLabelInPlural)
            || $this->entityLabelInPlural instanceof TranslatableInterface
        ) {
            return $this->entityLabelInPlural;
        }

        return ($this->entityLabelInPlural)($entityInstance, $pageName);
    }

    /**
     * @param TranslatableInterface|string|callable $label
     */
    public function setEntityLabelInPlural($label): void
    {
        if (null !== $label && !\is_string($label) && !$label instanceof TranslatableInterface && !\is_callable($label)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.27.0',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$label',
                __METHOD__,
                '"string" or "TranslatableInterface" or "callable" or "null"',
                \gettype($label)
            );
        }
        $this->entityLabelInPlural = $label;
    }

    /**
     * @param object|null          $entityInstance
     * @param array<string, mixed> $translationParameters
     */
    public function getCustomPageTitle(?string $pageName = null, /* ?object */ $entityInstance = null, array $translationParameters = [], ?string $domain = null): ?TranslatableInterface
    {
        $title = $this->customPageTitles[$pageName ?? $this->pageName];
        if (\is_callable($title)) {
            $title = null !== $entityInstance ? $title($entityInstance) : $title();
        }

        if (null === $title) {
            return null;
        }

        if ($title instanceof TranslatableInterface) {
            return TranslatableMessageBuilder::withParameters($title, $translationParameters);
        }

        return t($title, $translationParameters, $domain);
    }

    /**
     * @param TranslatableInterface|string|callable $pageTitle
     */
    public function setCustomPageTitle(string $pageName, $pageTitle): void
    {
        if (!\is_string($pageTitle) && !$pageTitle instanceof TranslatableInterface && !\is_callable($pageTitle)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$pageTitle',
                __METHOD__,
                '"string" or "callable"',
                \gettype($pageTitle)
            );
        }

        $this->customPageTitles[$pageName] = $pageTitle;
    }

    /**
     * @param object|null          $entityInstance
     * @param array<string, mixed> $translationParameters
     */
    public function getDefaultPageTitle(?string $pageName = null, /* ?object */ $entityInstance = null, array $translationParameters = []): ?TranslatableInterface
    {
        if (!\is_object($entityInstance)
            && null !== $entityInstance) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$entityInstance',
                __METHOD__,
                '"object" or "null"',
                \gettype($entityInstance)
            );
        }

        if (null !== $entityInstance) {
            if ($entityInstance instanceof \Stringable) {
                $entityAsString = (string) $entityInstance;

                if ('' !== $entityAsString) {
                    return t($entityAsString, $translationParameters, 'EasyAdminBundle');
                }
            }
        }

        if (!isset($this->defaultPageTitles[$pageName ?? $this->pageName])) {
            return null;
        }

        return t($this->defaultPageTitles[$pageName ?? $this->pageName], $translationParameters, 'EasyAdminBundle');
    }

    public function getHelpMessage(?string $pageName = null): TranslatableInterface|string
    {
        return $this->helpMessages[$pageName ?? $this->pageName ?? ''] ?? '';
    }

    /**
     * @return array<string|TranslatableInterface|null>
     */
    public function getHelpMessages(): array
    {
        return $this->helpMessages;
    }

    public function setHelpMessage(string $pageName, TranslatableInterface|string $helpMessage): void
    {
        $this->helpMessages[$pageName] = $helpMessage;
    }

    public function getDatePattern(): ?string
    {
        return $this->datePattern;
    }

    public function setDatePattern(?string $format): void
    {
        $this->datePattern = $format;
    }

    public function getTimePattern(): ?string
    {
        return $this->timePattern;
    }

    public function setTimePattern(?string $format): void
    {
        $this->timePattern = $format;
    }

    /**
     * @return array{string, string}
     */
    public function getDateTimePattern(): array
    {
        return $this->dateTimePattern;
    }

    public function setDateTimePattern(string $dateFormatOrPattern, string $timeFormat = DateTimeField::FORMAT_NONE): void
    {
        $this->dateTimePattern = [$dateFormatOrPattern, $timeFormat];
    }

    public function getDateIntervalFormat(): string
    {
        return $this->dateIntervalFormat;
    }

    public function setDateIntervalFormat(string $format): void
    {
        $this->dateIntervalFormat = $format;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezoneId): void
    {
        $this->timezone = $timezoneId;
    }

    public function getNumberFormat(): ?string
    {
        return $this->numberFormat;
    }

    public function setNumberFormat(string $numberFormat): void
    {
        $this->numberFormat = $numberFormat;
    }

    public function getThousandsSeparator(): ?string
    {
        return $this->thousandsSeparator;
    }

    public function setThousandsSeparator(string $separator): void
    {
        $this->thousandsSeparator = $separator;
    }

    public function getDecimalSeparator(): ?string
    {
        return $this->decimalSeparator;
    }

    public function setDecimalSeparator(string $separator): void
    {
        $this->decimalSeparator = $separator;
    }

    /**
     * @return array<string, 'ASC'|'DESC'>
     */
    public function getDefaultSort(): array
    {
        return $this->defaultSort;
    }

    /**
     * @param array<string, 'ASC'|'DESC'> $defaultSort
     */
    public function setDefaultSort(array $defaultSort): void
    {
        $this->defaultSort = $defaultSort;
    }

    public function getSearchMode(): string
    {
        return $this->searchMode;
    }

    public function setSearchMode(string $searchMode): void
    {
        $this->searchMode = $searchMode;
    }

    /**
     * @return array<string>|null
     */
    public function getSearchFields(): ?array
    {
        return $this->searchFields;
    }

    /**
     * @param array<string>|null $searchFields
     */
    public function setSearchFields(?array $searchFields): void
    {
        $this->searchFields = $searchFields;
    }

    public function autofocusSearch(): bool
    {
        return $this->autofocusSearch;
    }

    public function setAutofocusSearch(bool $autofocusSearch): void
    {
        $this->autofocusSearch = $autofocusSearch;
    }

    public function isSearchEnabled(): bool
    {
        return null !== $this->searchFields;
    }

    public function showEntityActionsAsDropdown(): bool
    {
        return $this->showEntityActionsAsDropdown;
    }

    public function setShowEntityActionsAsDropdown(bool $showAsDropdown): void
    {
        $this->showEntityActionsAsDropdown = $showAsDropdown;
    }

    public function getPaginator(): PaginatorDto
    {
        return $this->paginatorDto;
    }

    public function setPaginator(PaginatorDto $paginatorDto): void
    {
        $this->paginatorDto = $paginatorDto;
    }

    /**
     * @return array<string, string>
     */
    public function getOverriddenTemplates(): array
    {
        return $this->overriddenTemplates;
    }

    public function overrideTemplate(string $templateName, string $templatePath): void
    {
        $this->overriddenTemplates[$templateName] = $templatePath;
    }

    /**
     * @return array<string>
     */
    public function getFormThemes(): array
    {
        return $this->formThemes;
    }

    public function addFormTheme(string $formThemePath): void
    {
        // fields form themes are added last to give them more priority
        $this->formThemes = array_merge($this->formThemes, [$formThemePath]);
    }

    /**
     * @param array<string> $formThemes
     */
    public function setFormThemes(array $formThemes): void
    {
        $this->formThemes = $formThemes;
    }

    public function getNewFormOptions(): KeyValueStore
    {
        return $this->newFormOptions;
    }

    public function getEditFormOptions(): KeyValueStore
    {
        return $this->editFormOptions;
    }

    public function setNewFormOptions(KeyValueStore $formOptions): void
    {
        $this->newFormOptions = $formOptions;
    }

    public function setEditFormOptions(KeyValueStore $formOptions): void
    {
        $this->editFormOptions = $formOptions;
    }

    public function getEntityPermission(): string|Expression|null
    {
        return $this->entityPermission;
    }

    public function setEntityPermission(string|Expression $entityPermission): void
    {
        $this->entityPermission = $entityPermission;
    }

    public function getCurrentAction(): string
    {
        return $this->actionName;
    }

    public function setCurrentAction(string $actionName): void
    {
        $this->actionName = $actionName;
    }

    public function getActionsConfig(): ActionConfigDto
    {
        return $this->actionConfigDto;
    }

    public function setActionsConfig(ActionConfigDto $actionConfig): void
    {
        $this->actionConfigDto = $actionConfig;
    }

    public function getFiltersConfig(): FilterConfigDto
    {
        return $this->filters;
    }

    public function setFiltersConfig(FilterConfigDto $filterConfig): void
    {
        $this->filters = $filterConfig;
    }

    public function getContentWidth(): ?string
    {
        return $this->contentWidth;
    }

    public function setContentWidth(string $contentWidth): void
    {
        $this->contentWidth = $contentWidth;
    }

    public function getSidebarWidth(): ?string
    {
        return $this->sidebarWidth;
    }

    public function setSidebarWidth(string $sidebarWidth): void
    {
        $this->sidebarWidth = $sidebarWidth;
    }

    public function areNullValuesHidden(): bool
    {
        return $this->hideNullValues;
    }

    public function hideNullValues(bool $hide): void
    {
        $this->hideNullValues = $hide;
    }

    public function askConfirmationOnBatchActions(): bool|string|TranslatableInterface
    {
        return $this->askConfirmationOnBatchActions;
    }

    public function setAskConfirmationOnBatchActions(bool|string|TranslatableInterface $askConfirmation): void
    {
        $this->askConfirmationOnBatchActions = $askConfirmation;
    }
}
