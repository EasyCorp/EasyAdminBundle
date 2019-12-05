<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

trait CommonFormThemeConfigTrait
{
    private $formThemes = ['@EasyAdmin/form_theme.html.twig'];

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
}
