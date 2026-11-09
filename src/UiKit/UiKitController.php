<?php

namespace EasyCorp\Bundle\EasyAdminBundle\UiKit;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\GrayScale;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\IconSet;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ThemeColor;
use EasyCorp\Bundle\EasyAdminBundle\Config\Theme;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CurrencyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\LanguageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\LocaleField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

/**
 * Renders the UI Kit inside the current dashboard: the foundations of the design
 * (typography, accent colors and fonts) and the EasyAdmin Twig components with their
 * examples. Each example is a Twig template that is rendered and also shown as source code.
 * The routes only exist when the UI Kit is enabled (by default, in debug mode).
 *
 * @internal
 */
#[AdminRoute(path: '/_ea_ui_kit', name: 'ea_ui_kit')]
final class UiKitController
{
    private const COMPONENTS_TEMPLATE_PREFIX = '@EasyAdmin/ui_kit/components';
    private const FOUNDATIONS = [
        'typography' => 'Typography',
        'colors' => 'Colors',
        'fonts' => 'Fonts',
    ];
    private const FORM_FIELD_STATES = [
        'default' => 'Default',
        'help' => 'Help',
        'errors' => 'Errors',
        'disabled' => 'Disabled',
    ];
    private const TYPOGRAPHY_SAMPLE_TEMPLATE = '@EasyAdmin/ui_kit/foundations/_typography_sample.html.twig';
    // the namespace mapped to the 'ea:' prefix in EasyAdminExtension::prepend()
    private const COMPONENTS_NAMESPACE = 'EasyCorp\\Bundle\\EasyAdminBundle\\Twig\\Component\\';
    // these examples use FontAwesome icon names, which only work when the dashboard uses that icon set
    private const FONTAWESOME_EXAMPLES = ['icon/01_fontawesome'];
    // examples render internal icons so they work with any icon set, but their source shows
    // the names people write with their icon set: [FontAwesome name, Lucide name of UX Icons]
    private const DISPLAYED_ICON_NAMES = [
        'check' => ['fa-solid fa-check', 'check'],
        'chevron-right' => ['fa-solid fa-chevron-right', 'chevron-right'],
        'circle-exclamation' => ['fa-solid fa-circle-exclamation', 'circle-alert'],
        'circle-info' => ['fa-solid fa-circle-info', 'info'],
        'delete' => ['fa-regular fa-trash-can', 'trash'],
        'detail' => ['fa-regular fa-eye', 'eye'],
        'dots-horizontal' => ['fa-solid fa-ellipsis', 'ellipsis'],
        'download' => ['fa-solid fa-download', 'download'],
        'edit' => ['fa-solid fa-pen', 'pencil'],
        'file-lines' => ['fa-regular fa-file-lines', 'file-text'],
        'folder-open' => ['fa-regular fa-folder-open', 'folder-open'],
        'gear' => ['fa-solid fa-gear', 'settings'],
        'home' => ['fa-solid fa-house', 'house'],
        'lock' => ['fa-solid fa-lock', 'lock'],
        'plus' => ['fa-solid fa-plus', 'plus'],
        'search' => ['fa-solid fa-magnifying-glass', 'search'],
        'user' => ['fa-solid fa-user', 'user'],
        'xmark' => ['fa-solid fa-xmark', 'x'],
    ];

    public function __construct(
        private readonly Environment $twig,
        private readonly TwigSourceHighlighter $highlighter,
        private readonly ComponentDocumentationReader $documentationReader,
        private readonly AdminContextProviderInterface $adminContextProvider,
        private readonly string $componentsDir,
        private readonly string $componentTemplatesDir,
        private readonly ControllerFactory $controllerFactory,
        private readonly FieldFactory $fieldFactory,
        private readonly FormFactory $formFactory,
    ) {
    }

    #[AdminRoute(path: '/', name: 'index', options: ['methods' => ['GET']])]
    public function index(): Response
    {
        return new Response($this->twig->render('@EasyAdmin/ui_kit/index.html.twig', [
            'foundations' => self::FOUNDATIONS,
            'components' => $this->findComponents(),
        ]));
    }

    #[AdminRoute(path: '/foundations/{name}', name: 'foundation', options: ['methods' => ['GET'], 'requirements' => ['name' => '[a-z0-9_]+']])]
    public function foundation(string $name): Response
    {
        if (!\array_key_exists($name, self::FOUNDATIONS)) {
            throw new NotFoundHttpException(sprintf('The UI Kit doesn\'t include any foundation called "%s".', $name));
        }

        $parameters = match ($name) {
            'typography' => [
                'sample_template' => self::TYPOGRAPHY_SAMPLE_TEMPLATE,
                'highlighted_sample' => $this->highlighter->highlight(rtrim($this->twig->getLoader()->getSourceContext(self::TYPOGRAPHY_SAMPLE_TEMPLATE)->getCode())),
            ],
            'colors' => ['gray_options' => $this->getGrayScaleOptions(), 'accent_color_options' => $this->getAccentColorOptions()],
            'fonts' => ['font_options' => $this->getFontOptions()],
        };

        return new Response($this->twig->render(sprintf('@EasyAdmin/ui_kit/foundations/%s.html.twig', $name), [
            'foundation_dir_name' => $name,
            'foundation_name' => self::FOUNDATIONS[$name],
        ] + $parameters));
    }

    #[AdminRoute(path: '/components/{name}', name: 'component', options: ['methods' => ['GET'], 'requirements' => ['name' => '[a-z0-9_]+']])]
    public function component(string $name): Response
    {
        $components = $this->findComponents();
        if (!\array_key_exists($name, $components)) {
            throw new NotFoundHttpException(sprintf('The UI Kit doesn\'t include any component called "%s".', $name));
        }

        return new Response($this->twig->render('@EasyAdmin/ui_kit/component.html.twig', [
            'component_dir_name' => $name,
            'component_name' => $components[$name],
            'examples' => $this->findExamples($name),
            'documentation' => $this->findDocumentation($components[$name]),
            'sub_components_documentation' => $this->findSubComponentsDocumentation($components[$name]),
        ]));
    }

    /**
     * Renders every native field with the same factories that render the forms of the
     * CRUD controllers, so the fields look and behave like in the real backend.
     */
    #[AdminRoute(path: '/form_fields', name: 'form_fields', options: ['methods' => ['GET']])]
    public function formFields(Request $request): Response
    {
        $context = $this->adminContextProvider->getContext();
        if (!$context instanceof AdminContext) {
            throw new \LogicException('The form field reference of the UI Kit can only be rendered inside a dashboard.');
        }

        $dashboardController = $this->controllerFactory->getDashboardControllerInstance($context->getDashboardControllerFqcn(), $request)
            ?? throw new \LogicException(sprintf('The "%s" dashboard controller can\'t be created.', $context->getDashboardControllerFqcn()));

        // the CRUD config of the dashboard applies to all the CRUD controllers (e.g. form themes, date formats)
        $crudDto = $dashboardController->configureCrud()->getAsDto();
        $crudDto->setEntityFqcn(FormFieldReference::class);
        $crudDto->setCurrentAction(Action::NEW);
        $crudDto->setPageName(Crud::PAGE_NEW);
        // there's no CRUD controller to run the actions or the search, so they're disabled
        $actionsConfig = new ActionConfigDto();
        $actionsConfig->disableActions([Action::INDEX, Action::DETAIL, Action::EDIT, Action::NEW, Action::DELETE, Action::BATCH_DELETE]);
        $crudDto->setActionsConfig($actionsConfig);
        $crudDto->setSearchFields(null);

        // Doctrine metadata without any mapping: the fields are configured explicitly,
        // so nothing has to be guessed from the type of the properties
        $metadata = new ClassMetadata(FormFieldReference::class);
        $metadata->setIdentifier(['id']);
        $entityDto = new EntityDto(FormFieldReference::class, $metadata, null, new FormFieldReference());

        // field configurators, form types and templates read the context from the request
        $context = self::createContextWithCrud($context, $crudDto, $entityDto);
        $request->attributes->set(EA::CONTEXT_REQUEST_ATTRIBUTE, $context);

        $state = $request->query->getString('state', 'default');
        if (!\array_key_exists($state, self::FORM_FIELD_STATES)) {
            throw new NotFoundHttpException(sprintf('The form field reference of the UI Kit doesn\'t include any state called "%s".', $state));
        }

        $fields = new FieldCollection($this->getReferenceFields());
        foreach ($fields as $fieldDto) {
            if ('help' === $state) {
                $fieldDto->setHelp('Lorem ipsum dolor sit amet, consectetur adipiscing elit.');
            } elseif ('disabled' === $state) {
                $fieldDto->setFormTypeOption('disabled', true);
            }
        }

        $this->fieldFactory->processFields($entityDto, $fields, Crud::PAGE_NEW);

        $fieldAssets = new AssetsDto();
        foreach ($entityDto->getFields() as $fieldDto) {
            $fieldAssets = $fieldAssets->mergeWith($fieldDto->getAssets()->loadedOn(Crud::PAGE_NEW));
        }
        $crudDto->setFieldAssets($fieldAssets);

        $formOptions = $crudDto->getNewFormOptions();
        if ('errors' === $state) {
            // the form is submitted with its own data (so no CSRF token is needed) because
            // fields are only rendered as invalid after submitting the form
            $formOptions->set('csrf_protection', false);
        }
        $form = $this->formFactory->createNewForm($entityDto, $formOptions, $context);

        if ('errors' === $state) {
            $form->submit(self::getSubmittedViewData($form));
            foreach ($form as $childForm) {
                // these errors would be displayed at the top of the form instead of next to the
                // field (this also excludes the form children that only define the layout)
                if ($childForm->getConfig()->getErrorBubbling()) {
                    continue;
                }

                $childForm->addError(new FormError('Lorem ipsum dolor sit amet.'));
            }
        }

        return new Response($this->twig->render('@EasyAdmin/ui_kit/form_fields.html.twig', [
            'form' => $form->createView(),
            'states' => self::FORM_FIELD_STATES,
            'current_state' => $state,
        ]));
    }

    /**
     * Creates a copy of the context of the current dashboard page with a CRUD, so the fields
     * are rendered like in a CRUD form. AdminContext doesn't expose its dashboard and i18n
     * contexts, so they are read with reflection; if those properties change, the tests of
     * the form field reference fail.
     *
     * @template TEntity of object
     *
     * @param EntityDto<TEntity> $entityDto
     *
     * @return AdminContext<TEntity>
     */
    private static function createContextWithCrud(AdminContext $context, CrudDto $crudDto, EntityDto $entityDto): AdminContext
    {
        /** @var DashboardContext $dashboardContext */
        $dashboardContext = (new \ReflectionProperty(AdminContext::class, 'dashboardContext'))->getValue($context);
        /** @var I18nContext $i18nContext */
        $i18nContext = (new \ReflectionProperty(AdminContext::class, 'i18nContext'))->getValue($context);

        return new AdminContext(
            new RequestContext($context->getRequest(), $context->getUser()),
            new CrudContext($crudDto, $entityDto, null, $context->getAdminControllers()),
            $dashboardContext,
            $i18nContext,
        );
    }

    /**
     * Returns the same data that the browser would submit for the given form as it's rendered.
     */
    private static function getSubmittedViewData(FormInterface $form): mixed
    {
        // expanded choices are compound, but browsers submit only the values of the checked
        // options, which is also the view data of the choice form (not of each child)
        if (!$form->getConfig()->getCompound() || self::isChoiceForm($form)) {
            return $form->getViewData();
        }

        $data = [];
        foreach ($form as $name => $childForm) {
            $data[$name] = self::getSubmittedViewData($childForm);
        }

        return $data;
    }

    private static function isChoiceForm(FormInterface $form): bool
    {
        for ($type = $form->getConfig()->getType(); null !== $type; $type = $type->getParent()) {
            if ($type->getInnerType() instanceof ChoiceType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<FieldInterface>
     */
    private function getReferenceFields(): array
    {
        $choices = ['Lorem' => 0, 'Ipsum' => 1, 'Dolor' => 2];

        return [
            FormField::addColumn('col-12'),
            FormField::addFieldset('Text fields'),
            TextField::new('text', 'TextField'),
            SlugField::new('slug', 'SlugField')->setTargetFieldName('text'),
            TextareaField::new('textarea', 'TextareaField'),
            TextEditorField::new('textEditor', 'TextEditorField'),
            CodeEditorField::new('codeEditor', 'CodeEditorField')->setLanguage('php'),
            EmailField::new('email', 'EmailField'),
            TelephoneField::new('telephone', 'TelephoneField'),
            UrlField::new('url', 'UrlField'),

            FormField::addColumn('col-12 col-xl-6'),
            FormField::addFieldset('Choice fields'),
            BooleanField::new('boolean', 'BooleanField'),
            ChoiceField::new('choice', 'ChoiceField')->setChoices($choices),
            ChoiceField::new('choiceMultiple', 'ChoiceField (multiple)')->setChoices($choices)->allowMultipleChoices(),
            ChoiceField::new('choiceAutocomplete', 'ChoiceField (autocomplete)')->setChoices($choices)->allowMultipleChoices()->autocomplete(),
            ChoiceField::new('choiceExpanded', 'ChoiceField (expanded)')->setChoices($choices)->renderExpanded(),
            ChoiceField::new('choiceExpandedMultiple', 'ChoiceField (expanded and multiple)')->setChoices($choices)->allowMultipleChoices()->renderExpanded(),

            FormField::addFieldset('Numeric fields'),
            IntegerField::new('integer', 'IntegerField'),
            NumberField::new('number', 'NumberField'),
            PercentField::new('percent', 'PercentField'),
            MoneyField::new('money', 'MoneyField')->setCurrency('EUR'),

            FormField::addFieldset('Other fields'),
            AvatarField::new('avatar', 'AvatarField'),
            ColorField::new('color', 'ColorField'),
            IdField::new('id', 'IdField'),
            HiddenField::new('hidden', 'HiddenField'),

            FormField::addColumn('col-12 col-xl-6'),
            FormField::addFieldset('Date and time fields'),
            DateField::new('date', 'DateField'),
            TimeField::new('time', 'TimeField'),
            DateTimeField::new('dateTime', 'DateTimeField'),
            TimezoneField::new('timezone', 'TimezoneField'),

            FormField::addFieldset('Internationalization fields'),
            CountryField::new('country', 'CountryField'),
            CurrencyField::new('currency', 'CurrencyField'),
            LanguageField::new('language', 'LanguageField'),
            LocaleField::new('locale', 'LocaleField'),

            FormField::addFieldset('Collection fields'),
            ArrayField::new('array', 'ArrayField'),
            CollectionField::new('collection', 'CollectionField'),

            FormField::addFieldset('File fields'),
            ImageField::new('image', 'ImageField')->setUploadDir('public/uploads/images/'),
            FileField::new('file', 'FileField')->setUploadDir('public/uploads/files/'),
        ];
    }

    /**
     * The gray scales, with the CSS that applies each one to the page
     * (the same CSS that Theme::grays() generates).
     *
     * @return list<array{label: string, css: string, swatch: string, php: string}>
     */
    private function getGrayScaleOptions(): array
    {
        $options = [];
        foreach ((new \ReflectionClass(GrayScale::class))->getConstants() as $constantName => $grayScale) {
            $cssVariables = Theme::new()->grays($grayScale)->getAsDto()->getCssVariables();

            $options[] = [
                'label' => ucfirst($grayScale),
                'css' => self::getThemeCss($cssVariables),
                'swatch' => $cssVariables['common']['--gray-500'],
                'php' => sprintf('->grays(GrayScale::%s)', $constantName),
            ];
        }

        return $options;
    }

    /**
     * The predefined accent colors, with the CSS that applies each one to the page
     * (the same CSS that Theme::primaryColor() generates).
     *
     * @return list<array{label: string, css: string, swatch: string, php: string}>
     */
    private function getAccentColorOptions(): array
    {
        $options = [];
        foreach (ThemeColor::cases() as $color) {
            $cssVariables = Theme::new()->primaryColor($color)->getAsDto()->getCssVariables();
            // when a color doesn't define how it's displayed on surfaces, reset the value
            // that the current dashboard theme might have defined
            $cssVariables['light'] += ['--ea-primary-on-surface' => 'var(--ea-primary)'];
            $cssVariables['dark'] += ['--ea-primary-on-surface' => 'var(--ea-primary-text)'];

            $options[] = [
                'label' => $color->name,
                'css' => self::getThemeCss($cssVariables),
                'swatch' => $cssVariables['common']['--ea-primary'],
                'php' => sprintf('->primaryColor(ThemeColor::%s)', $color->name),
            ];
        }

        return $options;
    }

    /**
     * The bundled fonts for text, with the CSS that applies each one to the page
     * (the same CSS that Theme::font() generates).
     *
     * @return list<array{label: string, css: string, php: string}>
     */
    private function getFontOptions(): array
    {
        $options = [];
        foreach (Font::cases() as $font) {
            if (Font::JetBrainsMono === $font) {
                continue;
            }

            $options[] = [
                'label' => $font->value,
                'css' => self::getThemeCss(Theme::new()->font(base: $font)->getAsDto()->getCssVariables()),
                'php' => sprintf('->font(base: Font::%s)', $font->name),
            ];
        }

        return $options;
    }

    /**
     * Mirrors the <style> element that the layout renders for the dashboard theme.
     *
     * @param array{common: array<string, string>, light: array<string, string>, dark: array<string, string>} $cssVariables
     */
    private static function getThemeCss(array $cssVariables): string
    {
        $css = '';
        foreach (['common' => ':root, .ea-dark-scheme', 'light' => ':root', 'dark' => '.ea-dark-scheme'] as $group => $selector) {
            if ([] === $cssVariables[$group]) {
                continue;
            }

            $declarations = '';
            foreach ($cssVariables[$group] as $name => $value) {
                $declarations .= sprintf('%s: %s; ', $name, $value);
            }
            $css .= sprintf('%s { %s}', $selector, $declarations);
        }

        return $css;
    }

    /**
     * @return array<string, string> The directory name of each component (e.g. 'action_menu') mapped to its component name (e.g. 'ActionMenu')
     */
    private function findComponents(): array
    {
        $components = [];
        $componentDirs = glob($this->componentsDir.'/*', \GLOB_ONLYDIR);
        foreach (false === $componentDirs ? [] : $componentDirs as $componentDir) {
            $dirName = basename($componentDir);
            $components[$dirName] = str_replace('_', '', ucwords($dirName, '_'));
        }
        ksort($components);

        return $components;
    }

    /**
     * @return array{props: list<array<string, mixed>>, blocks: list<array<string, mixed>>}|null
     */
    private function findDocumentation(string $componentPath): ?array
    {
        $templateName = sprintf('@EasyAdmin/components/%s.html.twig', $componentPath);
        $documentation = $this->twig->getLoader()->exists($templateName) ? $this->documentationReader->read($templateName) : null;

        // the props of class-backed components are read from their PHP class, so they
        // are available even when Twig doesn't support documentation comments
        $componentClass = self::COMPONENTS_NAMESPACE.str_replace('/', '\\', $componentPath);
        if (class_exists($componentClass)) {
            $documentation = [
                'props' => $this->documentationReader->readClassProps($componentClass),
                'blocks' => $documentation['blocks'] ?? [],
            ];
        }

        return $documentation;
    }

    /**
     * Sub-components are the templates stored in the directory of the component (e.g. 'Modal:Trigger').
     *
     * @return array<string, array{props: list<array<string, mixed>>, blocks: list<array<string, mixed>>}>
     */
    private function findSubComponentsDocumentation(string $componentName): array
    {
        $subComponentsDir = $this->componentTemplatesDir.'/'.$componentName;
        if (!is_dir($subComponentsDir)) {
            return [];
        }

        $subComponentPaths = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($subComponentsDir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if (str_ends_with($file->getFilename(), '.html.twig')) {
                $relativePath = substr($file->getPathname(), \strlen($this->componentTemplatesDir) + 1, -\strlen('.html.twig'));
                $subComponentPaths[] = str_replace('\\', '/', $relativePath);
            }
        }
        sort($subComponentPaths);

        $documentation = [];
        foreach ($subComponentPaths as $subComponentPath) {
            $subComponentDocumentation = $this->findDocumentation($subComponentPath);
            if ([] !== ($subComponentDocumentation['props'] ?? []) || [] !== ($subComponentDocumentation['blocks'] ?? [])) {
                $documentation[str_replace('/', ':', $subComponentPath)] = $subComponentDocumentation;
            }
        }

        return $documentation;
    }

    /**
     * Each template of the component directory is an example. Each subdirectory is a
     * group of related examples; its title and description are defined in the leading
     * comment of its _group.html.twig file.
     *
     * @return list<array<string, mixed>>
     */
    private function findExamples(string $componentDirName, bool $isGroup = false): array
    {
        if (false === $paths = glob(sprintf('%s/%s/*', $this->componentsDir, $componentDirName))) {
            return [];
        }
        natsort($paths);
        $assets = $this->adminContextProvider->getContext()?->getAssets();
        $usesFontAwesome = IconSet::FontAwesome === ($assets?->getIconSet() ?? IconSet::FontAwesome);
        $defaultIconPrefix = $assets?->getDefaultIconPrefix() ?? '';

        $examples = [];
        foreach ($paths as $path) {
            $name = basename($path);
            if (is_dir($path) && !$isGroup) {
                $groupDirName = $componentDirName.'/'.$name;
                $groupTemplate = sprintf('%s/%s/_group.html.twig', self::COMPONENTS_TEMPLATE_PREFIX, $groupDirName);
                $groupSource = $this->twig->getLoader()->exists($groupTemplate) ? $this->twig->getLoader()->getSourceContext($groupTemplate)->getCode() : '';
                [$title, $description] = $this->parseLeadingComment($groupSource, $name);

                $examples[] = [
                    'is_group' => true,
                    'id' => preg_replace('/^\d+_/', '', $name),
                    'title' => $title,
                    'description' => $description,
                    'examples' => $this->findExamples($groupDirName, true),
                ];

                continue;
            }

            if (!str_ends_with($name, '.html.twig') || str_starts_with($name, '_')) {
                continue;
            }

            $fileName = basename($name, '.html.twig');
            if (!$usesFontAwesome && \in_array($componentDirName.'/'.$fileName, self::FONTAWESOME_EXAMPLES, true)) {
                continue;
            }

            $template = sprintf('%s/%s/%s.html.twig', self::COMPONENTS_TEMPLATE_PREFIX, $componentDirName, $fileName);
            // read the source through the Twig loader (instead of the file) so it's the code
            // that renders the example (except for its icon names), even if the app overrides it
            $source = $this->twig->getLoader()->getSourceContext($template)->getCode();
            [$title, $description, $source] = $this->parseLeadingComment($source, $fileName);

            $usesApproximateIconNames = false;
            $source = preg_replace_callback(
                '/(?<=["\'])internal:([a-z0-9-]+)(?=["\'])/',
                static function (array $matches) use ($usesFontAwesome, $defaultIconPrefix, &$usesApproximateIconNames): string {
                    if (null === $iconNames = self::DISPLAYED_ICON_NAMES[$matches[1]] ?? null) {
                        return $matches[0];
                    }

                    if ($usesFontAwesome) {
                        return $iconNames[0];
                    }

                    $usesApproximateIconNames = true;

                    // the default prefix is added automatically to icon names without a prefix
                    return '' === $defaultIconPrefix ? 'lucide:'.$iconNames[1] : $iconNames[1];
                },
                $source
            );

            $examples[] = [
                'is_group' => false,
                'id' => preg_replace('/^\d+_/', '', $fileName),
                'template' => $template,
                'title' => $title,
                'description' => $description,
                'highlighted_code' => $this->highlighter->highlight(rtrim($source)),
                'uses_approximate_icon_names' => $usesApproximateIconNames,
            ];
        }

        return $examples;
    }

    /**
     * The leading comment of an example defines its title (first line) and description (rest).
     *
     * @return array{string, string, string} The title, the description and the source without the comment
     */
    private function parseLeadingComment(string $source, string $defaultTitle): array
    {
        if (1 !== preg_match('/^\s*\{#(.*?)#\}\s*/s', $source, $matches)) {
            return [$defaultTitle, '', $source];
        }

        $commentLines = explode("\n", trim($matches[1]), 2);

        return [
            trim($commentLines[0]),
            trim(preg_replace('/\s+/', ' ', $commentLines[1] ?? '')),
            substr($source, \strlen($matches[0])),
        ];
    }
}
