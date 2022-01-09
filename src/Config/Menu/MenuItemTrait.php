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

    /**
     * @param $content      This is rendered as the value of the badge; it can be anything that can be casted to a string (numbers, stringable objects, etc.)
     * @param string $style Pass one of these values for predefined styles: 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'
     *                      Otherwise, the passed value is applied "as is" to the `style` attribute of the HTML element of the badge
     */
    public function setBadge($content, string $style = 'secondary'): self
    {
        $this->dto->setBadge($content, $style);

        return $this;
    }

    public function getAsDto(): MenuItemDto
    {
        return $this->dto;
    }
}
