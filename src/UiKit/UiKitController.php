<?php

namespace EasyCorp\Bundle\EasyAdminBundle\UiKit;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

/**
 * Renders the UI Kit (EasyAdmin Twig components and their examples) inside the current dashboard.
 * Each example is a Twig template that is rendered and also shown as source code.
 * The routes only exist when the UI Kit is enabled (by default, in debug mode).
 *
 * @internal
 */
#[AdminRoute(path: '/_ea_ui_kit', name: 'ea_ui_kit')]
final class UiKitController
{
    private const COMPONENTS_TEMPLATE_PREFIX = '@EasyAdmin/ui_kit/components';
    // the namespace mapped to the 'ea:' prefix in EasyAdminExtension::prepend()
    private const COMPONENTS_NAMESPACE = 'EasyCorp\\Bundle\\EasyAdminBundle\\Twig\\Component\\';

    public function __construct(
        private readonly Environment $twig,
        private readonly TwigSourceHighlighter $highlighter,
        private readonly ComponentDocumentationReader $documentationReader,
        private readonly string $componentsDir,
        private readonly string $componentTemplatesDir,
    ) {
    }

    #[AdminRoute(path: '/', name: 'index', options: ['methods' => ['GET']])]
    public function index(): Response
    {
        return new Response($this->twig->render('@EasyAdmin/ui_kit/index.html.twig', [
            'components' => $this->findComponents(),
        ]));
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
     * @return list<array{id: string, template: string, title: string, description: string, highlighted_code: string}>
     */
    private function findExamples(string $componentDirName): array
    {
        $examples = [];
        if (false === $exampleFiles = glob(sprintf('%s/%s/*.html.twig', $this->componentsDir, $componentDirName))) {
            return [];
        }
        natsort($exampleFiles);

        foreach ($exampleFiles as $exampleFile) {
            $fileName = basename($exampleFile, '.html.twig');
            $template = sprintf('%s/%s/%s.html.twig', self::COMPONENTS_TEMPLATE_PREFIX, $componentDirName, $fileName);
            // read the source through the Twig loader (instead of the file) so it's
            // exactly the code that renders the example, even if the app overrides it
            $source = $this->twig->getLoader()->getSourceContext($template)->getCode();

            // the leading comment of each example defines its title (first line) and description (rest)
            $title = $fileName;
            $description = '';
            if (1 === preg_match('/^\s*\{#(.*?)#\}\s*/s', $source, $matches)) {
                $commentLines = explode("\n", trim($matches[1]), 2);
                $title = trim($commentLines[0]);
                $description = trim(preg_replace('/\s+/', ' ', $commentLines[1] ?? ''));
                $source = substr($source, \strlen($matches[0]));
            }

            $examples[] = [
                'id' => preg_replace('/^\d+_/', '', $fileName),
                'template' => $template,
                'title' => $title,
                'description' => $description,
                'highlighted_code' => $this->highlighter->highlight(rtrim($source)),
            ];
        }

        return $examples;
    }
}
