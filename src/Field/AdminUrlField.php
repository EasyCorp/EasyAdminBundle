<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @author Honza Novak <jan.novak@id-sign.com>
 */
final class AdminUrlField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_ACTION = 'action';
    public const OPTION_CONTROLLER = 'controller';
    public const OPTION_DASHBOARD = 'dashboard';
    public const OPTION_RENDER_AS_LINK = 'render_as_link';

    /**
     * @param string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/admin_url')
            ->setFormType(TextType::class)
            ->addCssClass('field-adminurl')
            ->setDefaultColumns('col-md-6 col-xxl-5')
            ->setCustomOption(self::OPTION_RENDER_AS_LINK, true);
    }

    public function setDashboard(string $dashboard): self
    {
        $this->setCustomOption(self::OPTION_DASHBOARD, $dashboard);
        return $this;
    }

    public function setController(string $controller): self
    {
        $this->setCustomOption(self::OPTION_CONTROLLER, $controller);
        return $this;
    }

    public function setAction(string $action): self
    {
        $this->setCustomOption(self::OPTION_ACTION, $action);
        return $this;
    }

    public function renderAsLink($asLink = true): self {
        $this->setCustomOption(self::OPTION_RENDER_AS_LINK, $asLink);
        return $this;
    }
}
