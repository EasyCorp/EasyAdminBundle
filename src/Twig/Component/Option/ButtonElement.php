<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
enum ButtonElement: string
{
    case A = 'a';
    case Button = 'button';
    case Form = 'form';

    public function isLink(): bool
    {
        return self::A === $this;
    }

    public function isButton(): bool
    {
        return self::Button === $this;
    }

    public function isForm(): bool
    {
        return self::Form === $this;
    }
}
