<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ControllersDto
{
    private bool $allowByDefault = true;
    /** @var array<string> The FQCN of the allowed CRUD controllers */
    private array $allowedControllers = [];
    /** @var array<string> The FQCN of the disallowed CRUD controllers */
    private array $disallowedControllers = [];

    public function __construct()
    {
    }

    public function setAllowAll(bool $allowAll): void
    {
        $this->allowByDefault = $allowAll;
        $this->disallowedControllers = [];
    }

    public function setAllowNone(bool $allowNone): void
    {
        $this->allowByDefault = !$allowNone;
        $this->allowedControllers = [];
    }

    public function setAllowOnly(array $allowedControllers): void
    {
        $this->allowByDefault = false;
        $this->allowedControllers = $allowedControllers;
    }

    public function setAllowAllExcept(array $disallowedControllers): void
    {
        $this->allowByDefault = true;
        $this->disallowedControllers = $disallowedControllers;
    }

    public function setDisallowOnly(array $disallowedControllers): void
    {
        $this->allowByDefault = true;
        $this->disallowedControllers = $disallowedControllers;
    }

    public function setDisallowAllExcept(array $allowedControllers): void
    {
        $this->allowByDefault = false;
        $this->allowedControllers = $allowedControllers;
    }

    public function allowByDefault(): bool
    {
        return $this->allowByDefault;
    }

    public function getAllowedControllers(): array
    {
        return $this->allowedControllers;
    }

    public function getDisallowedControllers(): array
    {
        return $this->disallowedControllers;
    }
}
