<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudPageDto;

final class FormPageConfig
{
    private $pageName = 'form';
    private $title;
    private $help;
    private $formOptions = [];
    private $showSaveAndExitButton = true;
    private $showSaveAndContinueButton = false;
    private $showSaveAndAddAnotherButton = false;

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

    public function setFormOptions(array $formOptions): self
    {
        $this->formOptions = $formOptions;

        return $this;
    }

    public function showSaveAndExitButton(bool $showButton = true): self
    {
        $this->showSaveAndExitButton = $showButton;

        return $this;
    }

    public function showSaveAndContinueButton(bool $showButton = true): self
    {
        $this->showSaveAndContinueButton = $showButton;

        return $this;
    }

    public function showSaveAndAddAnotherButton(bool $showButton = true): self
    {
        $this->showSaveAndAddAnotherButton = $showButton;

        return $this;
    }

    public function getAsDto(): CrudPageDto
    {
        return CrudPageDto::newFromFormPage($this->pageName, $this->title, $this->help, $this->formOptions, $this->showSaveAndExitButton, $this->showSaveAndContinueButton, $this->showSaveAndAddAnotherButton);
    }
}
