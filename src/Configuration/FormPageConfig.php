<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudPageDto;

final class FormPageConfig
{
    use CommonPageConfigTrait;

    private $pageName = 'form';
    private $formOptions = [];

    public static function new(): self
    {
        $config = new self();

        $config
            ->addAction(Action::SAVE_AND_CONTINUE)
            ->addAction(Action::SAVE_AND_RETURN);

        return $config;
    }

    public function setFormOptions(array $formOptions): self
    {
        $this->formOptions = $formOptions;

        return $this;
    }

    public function getAsDto(): CrudPageDto
    {
        return CrudPageDto::newFromFormPage($this->pageName, $this->title, $this->help, $this->permission, $this->formOptions, $this->actions, $this->disabledActions, $this->actionUpdateCallables);
    }
}
