<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudPageDto;

final class FormPageConfig
{
    private $pageName = 'form';
    private $title;
    private $help;
    private $formThemes = [];
    private $formOptions = [];

    public static function new(): self
    {
        return new self();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setHelp(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function addFormTheme(string $themePath): self
    {
        array_unshift($this->formThemes, $themePath);

        return $this;
    }

    public function setFormThemes(array $themePaths): self
    {
        foreach ($themePaths as $path) {
            if (!\is_string($path)) {
                throw new \InvalidArgumentException(sprintf('All form theme paths must be strings, but "%s" was provided in "%s"', gettype($path), (string) $path));
            }
        }

        $this->formThemes = $themePaths;

        return $this;
    }

    public function setFormOptions(array $formOptions): self
    {
        $this->formOptions = $formOptions;

        return $this;
    }

    public function getAsDto(): CrudPageDto
    {
        return CrudPageDto::newFromFormPage($this->pageName, $this->title, $this->help, $this->formThemes, $this->formOptions);
    }
}
