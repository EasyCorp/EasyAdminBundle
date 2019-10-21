<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface FieldInterface
{
    // mandatory options for all fields
    public function setDefaultOptions(OptionsResolver $resolver): void;

    // custom options defined by a particular field
    public function setCustomOptions(OptionsResolver $resolver): void;

    // optional CSS, JS assets needed by this field and added to the rendered page
    public function addAssets(): array;
}
