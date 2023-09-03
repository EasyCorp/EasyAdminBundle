<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface TemplateRegistryInterface
{
    public function has(string $templateName): bool;

    public function get(string $templateName): string;

    public function setTemplate(string $templateName, string $templatePath): void;

    public function setTemplates(array $templateNamesAndPaths): void;
}
