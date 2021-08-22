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

    /**
     * @param string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplatePath('crud/field/admin_url')
            ->setFormType(TextType::class)
            ->addCssClass('field-adminurl')
            ->setDefaultColumns('col-md-6 col-xxl-5');
    }

    public function setDashboard(string $dashboard): AdminUrlField
    {
        $this->setCustomOption(self::OPTION_DASHBOARD, $dashboard);
        return $this;
    }

    public function setController(string $controller): AdminUrlField
    {
        $this->setCustomOption(self::OPTION_CONTROLLER, $controller);
        return $this;
    }

    public function setAction(string $action): AdminUrlField
    {
        $this->setCustomOption(self::OPTION_ACTION, $action);
        return $this;
    }
}
