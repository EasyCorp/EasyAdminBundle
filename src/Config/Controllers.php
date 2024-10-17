<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\ControllersDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Controllers
{
    private ControllersDto $dto;

    private function __construct(ControllersDto $controllersDto)
    {
        $this->dto = $controllersDto;
    }

    public static function new(): self
    {
        $dto = new ControllersDto();

        return new self($dto);
    }

    public function allowAll(): self
    {
        $this->dto->setAllowAll(true);

        return $this;
    }

    public function allowNone(): self
    {
        $this->dto->setAllowNone(true);

        return $this;
    }

    public function allowOnly(string ...$crudControllerFqcn): self
    {
        $this->dto->setAllowOnly($crudControllerFqcn);

        return $this;
    }

    public function allowAllExcept(string ...$crudControlleFqcn): self
    {
        $this->dto->setAllowAllExcept($crudControlleFqcn);

        return $this;
    }

    public function disallowOnly(string ...$crudControllerFqcn): self
    {
        $this->dto->setDisallowOnly($crudControllerFqcn);

        return $this;
    }

    public function disallowAllExcept(string ...$crudControllerFqcn): self
    {
        $this->dto->setDisallowAllExcept($crudControllerFqcn);

        return $this;
    }

    public function getAsDto(): ControllersDto
    {
        return $this->dto;
    }
}
