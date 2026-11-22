<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetInterface;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetTrait;
use Symfony\Contracts\Translation\TranslatableInterface;

final class DummyWidget implements WidgetInterface
{
    use WidgetTrait;

    public static function new(TranslatableInterface|string|null $label = null): self
    {
        return (new self())
            ->setLabel($label)
            ->setTemplatePath('dummy.html.twig')
            ->setDefaultColumns(4);
    }

    public function setValue(string $name, mixed $valueOrCallable): self
    {
        return $this->setDataEntry($name, $valueOrCallable);
    }
}
