<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
trait MenuItemTrait
{
    /** @var MenuItemDto */
    private $dto;

    public function setCssClass(string $cssClass): self
    {
        $this->dto->setCssClass($cssClass);

        return $this;
    }

    public function setQueryParameter(string $parameterName, $parameterValue): self
    {
        $this->dto->setRouteParameter($parameterName, $parameterValue);

        return $this;
    }

    public function setPermission(string $permission): self
    {
        $this->dto->setPermission($permission);

        return $this;
    }

    public function setTranslationParameters(array $parameters): self
    {
        $this->dto->setTranslationParameters($parameters);

        return $this;
    }

    public function setLinkRel(string $rel): self
    {
        $this->dto->setLinkRel($rel);

        return $this;
    }

    public function setLinkTarget(string $target): self
    {
        $this->dto->setLinkTarget($target);

        return $this;
    }

    public function getAsDto(): MenuItemDto
    {
        return $this->dto;
    }
}
