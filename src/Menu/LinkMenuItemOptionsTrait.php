<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

trait LinkMenuItemOptionsTrait
{
    private $linkRel = '';
    private $linkTarget = '_self';

    public function setLinkRel(string $rel): self
    {
        $this->linkRel = $rel;

        return $this;
    }

    public function setLinkTarget(string $target): self
    {
        $this->linkTarget = $target;

        return $this;
    }
}
