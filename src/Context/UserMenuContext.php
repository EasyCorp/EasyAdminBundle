<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

final class UserMenuContext
{
    private $isNameDisplayed;
    private $isAvatarDisplayed;
    private $name;
    private $avatarUrl;
    private $items;

    public function __construct(bool $isNameDisplayed, bool $isAvatarDisplayed, ?string $name, ?string $avatarUrl, array $items)
    {
        $this->isNameDisplayed = $isNameDisplayed;
        $this->isAvatarDisplayed = $isAvatarDisplayed;
        $this->name = $name;
        $this->avatarUrl = $avatarUrl;
        $this->items = $items;
    }

    public function getIsNameDisplayed(): bool
    {
        return $this->isNameDisplayed;
    }

    public function getIsAvatarDisplayed(): bool
    {
        return $this->isAvatarDisplayed;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function withProperties(array $properties): self
    {
        foreach ($properties as $propertyName => $propertyValue) {
            if (!property_exists($this, $propertyName)) {
                throw new \InvalidArgumentException(sprintf('The "%s" option is not a valid action context option name. Valid option names are: %s', $propertyName, implode(', ', get_object_vars($this))));
            }

            $this->{$propertyName} = $propertyValue;
        }

        return $this;
    }
}
