<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionGroupDto;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonVariant;
use Symfony\Contracts\Translation\TranslatableInterface;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ActionGroup implements \Stringable
{
    // these are the action groups applied to a specific entity instance
    public const TYPE_ENTITY = 'entity';
    // these are the action groups that are not associated to an entity
    // (they are available only in the INDEX page)
    public const TYPE_GLOBAL = 'global';

    private function __construct(private readonly ActionGroupDto $dto)
    {
    }

    public function __toString()
    {
        return $this->dto->getName();
    }

    /**
     * @param TranslatableInterface|string|false|null $label Use FALSE to hide the label; use NULL to autogenerate it
     * @param string|null                             $icon  The full CSS classes of the icon to render
     */
    public static function new(string $name, TranslatableInterface|string|false|null $label = null, ?string $icon = null): self
    {
        $dto = new ActionGroupDto();
        $dto->setType(self::TYPE_ENTITY);
        $dto->setName($name);
        $dto->setLabel($label ?? self::humanizeString($name));
        $dto->setIcon($icon);
        $dto->setCssClass('');
        $dto->setActions([]);

        return new self($dto);
    }

    public function createAsGlobalActionGroup(): self
    {
        $this->dto->setType(self::TYPE_GLOBAL);

        return $this;
    }

    /**
     * If defined, the button will be rendered as a "split button", with a main clickable action
     * and a dropdown for the other actions. If not set, when clicking on the event, no action
     * is performed and only the dropdown is shown.
     */
    public function addMainAction(Action $action): self
    {
        $this->dto->setMainAction($action->getAsDto());

        return $this;
    }

    public function addAction(Action $action): self
    {
        $this->dto->addAction($action->getAsDto());

        return $this;
    }

    public function removeAction(string $actionName): self
    {
        $this->dto->removeAction($actionName);

        return $this;
    }

    /**
     * Use FALSE to hide the label; use NULL to autogenerate it.
     */
    public function setLabel(TranslatableInterface|string|false|null $label): self
    {
        $this->dto->setLabel($label ?? self::humanizeString($this->dto->getName()));

        return $this;
    }

    public function setIcon(?string $icon): self
    {
        $this->dto->setIcon($icon);

        return $this;
    }

    /**
     * Use this to override the default CSS classes applied to the dropdown and use instead your own CSS classes.
     * See also addCssClass() to add your own custom classes without removing the default ones.
     */
    public function setCssClass(string $cssClass): self
    {
        $this->dto->setCssClass(trim($cssClass));

        return $this;
    }

    /**
     * This adds the given CSS class(es) to the classes already applied to the dropdown.
     */
    public function addCssClass(string $cssClass): self
    {
        $this->dto->setAddedCssClass(trim($cssClass));

        return $this;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function setHtmlAttributes(array $attributes): self
    {
        $this->dto->setHtmlAttributes($attributes);

        return $this;
    }

    public function setTemplatePath(string $templatePath): self
    {
        $this->dto->setTemplatePath($templatePath);

        return $this;
    }

    /**
     * Adds a divider to separate groups of actions in the dropdown menu.
     */
    public function addDivider(): self
    {
        $this->dto->addDivider();

        return $this;
    }

    /**
     * Adds a header/label to organize actions in the dropdown menu.
     */
    public function addHeader(TranslatableInterface|string $label): self
    {
        $this->dto->addHeader($label);

        return $this;
    }

    /**
     * Controls when the dropdown should be displayed. The callable receives the current
     * entity DTO or null in the index page:
     *
     *     ->displayIf(
     *         static fn (?EntityDto $entityDto): bool => $entityDto?->getInstance()->isPublished()
     *     )
     */
    public function displayIf(callable $callable): self
    {
        $this->dto->setDisplayCallable($callable);

        return $this;
    }

    public function asDefaultActionGroup(bool $asDefaultActionGroup = true): self
    {
        if ($asDefaultActionGroup) {
            $this->dto->setVariant(ButtonVariant::Default);
        }

        return $this;
    }

    public function asPrimaryActionGroup(bool $asPrimaryActionGroup = true): self
    {
        if ($asPrimaryActionGroup) {
            $this->dto->setVariant(ButtonVariant::Primary);
        }

        return $this;
    }

    public function asSuccessActionGroup(bool $asSuccessActionGroup = true): self
    {
        if ($asSuccessActionGroup) {
            $this->dto->setVariant(ButtonVariant::Success);
        }

        return $this;
    }

    public function asWarningActionGroup(bool $asWarningActionGroup = true): self
    {
        if ($asWarningActionGroup) {
            $this->dto->setVariant(ButtonVariant::Warning);
        }

        return $this;
    }

    public function asDangerActionGroup(bool $asDangerActionGroup = true): self
    {
        if ($asDangerActionGroup) {
            $this->dto->setVariant(ButtonVariant::Danger);
        }

        return $this;
    }

    public function getAsDto(): ActionGroupDto
    {
        if (null === $this->dto->getIcon() && (null === $this->dto->getLabel() || false === $this->dto->getLabel())) {
            throw new \InvalidArgumentException(sprintf('The label and icon of an action group cannot be null at the same time. Either set the label, the icon or both for the "%s" action group.', $this->dto->getName()));
        }

        if (0 === \count($this->dto->getActions())) {
            throw new \InvalidArgumentException(sprintf('The "%s" action group must have at least one action. Use the "addAction()" method to add actions.', $this->dto->getName()));
        }

        // check if any action (including main action) has an icon (needed in the templates for design tweaks)
        $hasAnyActionWithIcon = false;
        if ($this->dto->hasMainAction() && null !== $this->dto->getMainAction()->getIcon()) {
            $hasAnyActionWithIcon = true;
        }

        if (!$hasAnyActionWithIcon) {
            foreach ($this->dto->getActions() as $action) {
                if (null !== $action->getIcon()) {
                    $hasAnyActionWithIcon = true;
                    break;
                }
            }
        }

        $this->dto->setHasAnyActionWithIcon($hasAnyActionWithIcon);

        return $this->dto;
    }

    private static function humanizeString(string $string): string
    {
        $uString = u($string);
        $upperString = $uString->upper()->toString();

        // this prevents humanizing all-uppercase labels (e.g. 'UUID' -> 'U u i d')
        // and other special labels which look better in uppercase
        if ($uString->toString() === $upperString) {
            return $upperString;
        }

        return $uString
            ->replaceMatches('/([A-Z])/', '_$1')
            ->replaceMatches('/[_\s]+/', ' ')
            ->trim()
            ->lower()
            ->title(true)
            ->toString();
    }
}
