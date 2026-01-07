<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

abstract class AbstractFieldTest extends KernelTestCase
{
    protected EntityDto $entityDto;
    protected $adminContext;
    protected $configurator;

    protected function getEntityDto(): EntityDto
    {
        $reflectedClass = new \ReflectionClass(ClassMetadata::class);
        $classMetadata = $reflectedClass->newInstanceWithoutConstructor();

        $reflectedClass = new \ReflectionClass(EntityDto::class);
        $entityDto = $reflectedClass->newInstanceWithoutConstructor();
        $instanceProperty = $reflectedClass->getProperty('instance');
        $instanceProperty->setValue($entityDto, new class {});
        $metadataProperty = $reflectedClass->getProperty('metadata');
        $metadataProperty->setValue($entityDto, $classMetadata);

        return $this->entityDto = $entityDto;
    }

    protected function getAdminContext(string $pageName, string $requestLocale, string $actionName, ?string $controllerFqcn = null): AdminContextInterface
    {
        self::bootKernel();

        $crudDto = new CrudDto();
        if ($controllerFqcn) {
            $crudDto->setControllerFqcn($controllerFqcn);
        }
        $crudDto->setPageName($pageName);
        $crudDto->setCurrentAction($actionName);
        $crudDto->setDatePattern(DateTimeField::FORMAT_MEDIUM);
        $crudDto->setTimePattern(DateTimeField::FORMAT_MEDIUM);
        $crudDto->setDateTimePattern(DateTimeField::FORMAT_MEDIUM, DateTimeField::FORMAT_MEDIUM);

        $request = new Request();
        $request->setLocale($requestLocale);

        return $this->adminContext = AdminContext::forTesting(
            requestContext: RequestContext::forTesting($request),
            crudContext: CrudContext::forTesting($crudDto),
            i18nContext: I18nContext::forTesting($requestLocale),
        );
    }

    protected function configure(FieldInterface $field, string $pageName = Crud::PAGE_INDEX, string $requestLocale = 'en', string $actionName = Action::INDEX, ?string $controllerFqcn = null): FieldDto
    {
        $fieldDto = $field->getAsDto();
        // Set the FieldFqcn so configurators can identify the field type
        if (null === $fieldDto->getFieldFqcn() || '' === $fieldDto->getFieldFqcn()) {
            $fieldDto->setFieldFqcn($field::class);
        }
        $this->configurator->configure($fieldDto, $this->getEntityDto(), $this->getAdminContext($pageName, $requestLocale, $actionName, $controllerFqcn));

        return $fieldDto;
    }

    /**
     * Renders a field template and returns the HTML output.
     *
     * This allows testing the actual HTML output of field templates,
     * which is useful for fields whose behavior is implemented in Twig templates
     * rather than in PHP configurators.
     */
    protected function renderFieldTemplate(
        FieldDto $fieldDto,
        EntityDto $entityDto,
        AdminContextInterface $adminContext,
        ?string $templatePath = null,
    ): string {
        if (!static::$booted) {
            static::bootKernel();
        }

        $container = static::getContainer();

        $request = new Request();
        $request->attributes->set(EA::CONTEXT_REQUEST_ATTRIBUTE, $adminContext);

        /** @var RequestStack $requestStack */
        $requestStack = $container->get('request_stack');
        $requestStack->push($request);

        try {
            /** @var Environment $twig */
            $twig = $container->get('twig');

            $template = $templatePath ?? $fieldDto->getTemplatePath();

            if (null === $template) {
                $template = $this->getDefaultTemplatePath($fieldDto);
            }

            return $twig->render($template, [
                'field' => $fieldDto,
                'entity' => $entityDto,
            ]);
        } finally {
            $requestStack->pop();
        }
    }

    /**
     * Derives the default template path from the field FQCN.
     */
    private function getDefaultTemplatePath(FieldDto $fieldDto): string
    {
        $fieldFqcn = $fieldDto->getFieldFqcn();
        if (null === $fieldFqcn) {
            throw new \LogicException('Field FQCN must be set to derive the template path');
        }

        $className = substr(strrchr($fieldFqcn, '\\'), 1);
        $fieldName = substr($className, 0, -5);
        $templateName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $fieldName));

        return sprintf('@EasyAdmin/crud/field/%s.html.twig', $templateName);
    }
}
