<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Displays the contents rendered by any Twig template of your application.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class TwigWidget implements WidgetInterface
{
    use WidgetTrait;

    public const OPTION_TEMPLATE_PATH = 'templatePath';
    public const OPTION_TEMPLATE_VARS = 'templateVars';

    public static function new(TranslatableInterface|string|null $label = null): self
    {
        $widget = (new self())
            ->setLabel($label)
            ->setTemplateName('widget/twig')
            ->setDefaultColumns(12)
            ->setCustomOption(self::OPTION_TEMPLATE_PATH, null)
            ->setCustomOption(self::OPTION_TEMPLATE_VARS, []);

        $widget->setDataEntry('template', static function (WidgetContext $context): string {
            $templatePath = $context->getWidget()->getCustomOption(self::OPTION_TEMPLATE_PATH);

            if (!\is_string($templatePath)) {
                throw new \LogicException(sprintf('The "%s" Twig widget doesn\'t define the template to render. Call its "setTemplate()" method.', $context->getWidget()->getName() ?? $context->getWidget()->getType()));
            }

            return $templatePath;
        });

        $widget->setDataEntry('vars', static function (WidgetContext $context): array {
            $vars = $context->resolve($context->getWidget()->getCustomOption(self::OPTION_TEMPLATE_VARS));

            if (!\is_array($vars)) {
                throw new \InvalidArgumentException(sprintf('The template variables of the "%s" Twig widget must be an array (%s given).', $context->getWidget()->getName() ?? $context->getWidget()->getType(), get_debug_type($vars)));
            }

            return $vars;
        });

        return $widget;
    }

    /**
     * @param string                        $path The path of the Twig template (e.g. 'admin/widget/welcome.html.twig')
     * @param array<string, mixed>|callable $vars The variables passed to the template or a closure that returns them;
     *                                            the template also receives the 'widget' and 'context' variables
     */
    public function setTemplate(string $path, array|callable $vars = []): self
    {
        if ('' === trim($path)) {
            throw new \InvalidArgumentException('The template path of a Twig widget cannot be empty.');
        }

        // strings can be the name of any function (e.g. 'phpinfo'), so they are never called;
        // arrays are always treated as the template variables, even if they look like a callable
        if (\is_string($vars)) {
            throw new \InvalidArgumentException(sprintf('The "%s()" method only accepts arrays, closures and invokable objects as template variables (string given).', __METHOD__));
        }

        $this->setCustomOption(self::OPTION_TEMPLATE_PATH, $path);
        $this->setCustomOption(self::OPTION_TEMPLATE_VARS, $vars);

        return $this;
    }
}
