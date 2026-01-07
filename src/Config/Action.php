<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonElement;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonStyle;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonType;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonVariant;
use Symfony\Contracts\Translation\TranslatableInterface;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Action implements \Stringable
{
    public const BATCH_DELETE = 'batchDelete';
    public const DELETE = 'delete';
    public const DETAIL = 'detail';
    public const EDIT = 'edit';
    public const INDEX = 'index';
    public const NEW = 'new';
    public const SAVE_AND_ADD_ANOTHER = 'saveAndAddAnother';
    public const SAVE_AND_CONTINUE = 'saveAndContinue';
    public const SAVE_AND_RETURN = 'saveAndReturn';

    // these are the actions applied to a specific entity instance
    public const TYPE_ENTITY = 'entity';
    // these are the actions that are not associated to an entity
    // (they are available only in the INDEX page)
    public const TYPE_GLOBAL = 'global';
    // these are actions that can be applied to one or more entities at the same time
    public const TYPE_BATCH = 'batch';

    private function __construct(private readonly ActionDto $dto)
    {
    }

    public function __toString()
    {
        return $this->dto->getName();
    }

    /**
     * @param TranslatableInterface|string|callable|false|null $label Use FALSE to hide the label; use NULL to autogenerate it
     * @param string|null                                      $icon  The full CSS classes of the FontAwesome icon to render (see https://fontawesome.com/v6/search?m=free)
     */
    public static function new(string $name, $label = null, ?string $icon = null): self
    {
        if (!\is_string($label)
            && !$label instanceof TranslatableInterface
            && !\is_callable($label)
            && false !== $label
            && null !== $label) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$label',
                __METHOD__,
                sprintf('"%s", "string", "callable", "false" or "null"', TranslatableInterface::class),
                \gettype($label)
            );
        }

        $dto = new ActionDto();
        $dto->setType(self::TYPE_ENTITY);
        $dto->setName($name);
        $dto->setLabel($label ?? self::humanizeString($name));
        $dto->setIcon($icon);
        $dto->setHtmlElement(ButtonElement::A);
        $dto->setHtmlAttributes([]);
        $dto->setTranslationParameters([]);

        return new self($dto);
    }

    public function createAsGlobalAction(): self
    {
        $this->dto->setType(self::TYPE_GLOBAL);

        return $this;
    }

    public function createAsBatchAction(): self
    {
        $this->dto->setType(self::TYPE_BATCH);

        return $this;
    }

    /**
     * @param TranslatableInterface|string|callable|false|null $label Use FALSE to hide the label; use NULL to autogenerate it
     */
    public function setLabel($label): self
    {
        if (!\is_string($label)
            && !$label instanceof TranslatableInterface
            && !\is_callable($label)
            && false !== $label
            && null !== $label) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$label',
                __METHOD__,
                sprintf('"%s", "string", "callable", "false" or "null"', TranslatableInterface::class),
                \gettype($label)
            );
        }

        $this->dto->setLabel($label ?? self::humanizeString($this->dto->getName()));

        return $this;
    }

    public function setIcon(?string $icon): self
    {
        $this->dto->setIcon($icon);

        return $this;
    }

    /**
     * Use this to override the default CSS classes applied to actions and use instead your own CSS classes.
     * See also addCssClass() to add your own custom classes without removing the default ones.
     */
    public function setCssClass(string $cssClass): self
    {
        $this->dto->setCssClass(trim($cssClass));

        return $this;
    }

    /**
     * This adds the given CSS class(es) to the classes already applied to the actions
     * (no matter if they are the default ones or some custom CSS classes set with the setCssClass() method).
     */
    public function addCssClass(string $cssClass): self
    {
        $this->dto->setAddedCssClass(trim($cssClass));

        return $this;
    }

    public function displayAsLink(): self
    {
        @trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.26.0',
            'The "%s()" method is deprecated and will be removed in 5.0.0. Use "%s()" instead.',
            __METHOD__,
            'renderAsLink()'
        );

        return $this->renderAsLink();
    }

    /**
     * This makes the button element to be `<a ...>` instead of `<button ...>` when rendering the action.
     * Visually, the action will look exactly the same as a button.
     */
    public function renderAsLink(bool $renderAsLink = true): self
    {
        if ($renderAsLink) {
            $this->dto->setHtmlElement(ButtonElement::A);
        }

        return $this;
    }

    public function displayAsButton(): self
    {
        @trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.26.0',
            'The "%s()" method is deprecated and will be removed in 5.0.0. Use "%s()" instead.',
            __METHOD__,
            'renderAsButton()'
        );

        return $this->renderAsButton();
    }

    /**
     * By default, actions are rendered as `<button type="submit" ...>` elements. This
     * method allows to change it and use a `<button type="button" ...>` element.
     */
    public function renderAsButton(ButtonType|string $buttonType = ButtonType::Submit): self
    {
        $this->dto->setHtmlElement('button');

        if (\is_string($buttonType)) {
            $buttonType = ButtonType::tryFrom($buttonType) ?? throw new \InvalidArgumentException(sprintf('Invalid button type "%s". Valid values are: %s', $buttonType, implode(', ', array_column(ButtonType::cases(), 'value'))));
        }
        $this->dto->setButtonType($buttonType);

        return $this;
    }

    public function displayAsForm(): self
    {
        @trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.26.0',
            'The "%s()" method is deprecated and will be removed in 5.0.0. Use "%s()" instead.',
            __METHOD__,
            'renderAsForm()'
        );

        return $this->renderAsForm();
    }

    /**
     * This makes the action to be rendered as a `<form method="post" ...>` element to
     * use the POST HTTP method when the action is triggered. Visually, the action will
     * look exactly the same as a regular button.
     */
    public function renderAsForm(bool $renderAsForm = true): self
    {
        if ($renderAsForm) {
            $this->dto->setHtmlElement('form');
        }

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

    public function linkToCrudAction(string $crudActionName): self
    {
        $this->dto->setCrudActionName($crudActionName);

        return $this;
    }

    /**
     * @param array<string, mixed>|callable $routeParameters The callable has the signature: function ($entity): array
     *
     * Route parameters can be defined as a callable with the signature: function ($entityInstance): array
     * Example: ->linkToRoute('invoice_send', fn (Invoice $entity) => ['uuid' => $entity->getId()]);
     */
    public function linkToRoute(string $routeName, array|callable $routeParameters = []): self
    {
        $this->dto->setRouteName($routeName);
        $this->dto->setRouteParameters($routeParameters);

        return $this;
    }

    /**
     * @param string|callable $url
     */
    public function linkToUrl($url): self
    {
        if (!\is_string($url) && !\is_callable($url)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$url',
                __METHOD__,
                '"string" or "callable"',
                \gettype($url)
            );
        }

        $this->dto->setUrl($url);

        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function setTranslationParameters(array $parameters): self
    {
        $this->dto->setTranslationParameters($parameters);

        return $this;
    }

    public function displayIf(callable $callable): self
    {
        $this->dto->setDisplayCallable($callable);

        return $this;
    }

    /**
     * By default, actions are rendered as `btn-secondary` buttons. This method applies
     * the same behavior, so you don't need to call it when creating custom actions.
     * It's mainly used internally when cloning existing action properties.
     */
    public function asDefaultAction(bool $asDefaultAction = true): self
    {
        if ($asDefaultAction) {
            $this->dto->setVariant(ButtonVariant::Default);
        }

        return $this;
    }

    /**
     * By default, actions are rendered as `btn-secondary` buttons. This method makes
     * the action to be rendered as a `btn-primary` button to stand out more in the UI.
     */
    public function asPrimaryAction(bool $asPrimaryAction = true): self
    {
        if ($asPrimaryAction) {
            $this->dto->setVariant(ButtonVariant::Primary);
        }

        return $this;
    }

    /**
     * By default, actions are rendered as `btn-secondary` buttons. This method makes
     * the action to be rendered as a `btn-success` button with green text.
     */
    public function asSuccessAction(bool $asSuccessAction = true): self
    {
        if ($asSuccessAction) {
            $this->dto->setVariant(ButtonVariant::Success);
        }

        return $this;
    }

    /**
     * By default, actions are rendered as `btn-secondary` buttons. This method makes
     * the action to be rendered as a `btn-warning` button with yellow text.
     */
    public function asWarningAction(bool $asWarningAction = true): self
    {
        if ($asWarningAction) {
            $this->dto->setVariant(ButtonVariant::Warning);
        }

        return $this;
    }

    /**
     * By default, actions are rendered as `btn-secondary` buttons. This method makes
     * the action to be rendered as a `btn-danger` button with red text. Use it for
     * destructive actions like 'delete'.
     */
    public function asDangerAction(bool $asDangerAction = true): self
    {
        if ($asDangerAction) {
            $this->dto->setVariant(ButtonVariant::Danger);
        }

        return $this;
    }

    /**
     * By default, actions are rendered as solid buttons. This method makes
     * the action to be rendered as a simple text link without button background
     * (the background is shown when hovering the action link).
     */
    public function asTextLink(bool $asTextLink = true): self
    {
        if ($asTextLink) {
            $this->dto->setStyle(ButtonStyle::Text);
        }

        return $this;
    }

    public function getAsDto(): ActionDto
    {
        if ((!$this->dto->isDynamicLabel() && null === $this->dto->getLabel()) && null === $this->dto->getIcon()) {
            throw new \InvalidArgumentException(sprintf('The label and icon of an action cannot be null at the same time. Either set the label, the icon or both for the "%s" action.', $this->dto->getName()));
        }

        if (null === $this->dto->getCrudActionName() && null === $this->dto->getRouteName() && null === $this->dto->getUrl()) {
            throw new \InvalidArgumentException(sprintf('Actions must link to either a route, a CRUD action, or a URL. Set the "linkToCrudAction()", "linkToRoute()", or "linkToUrl()" method for the "%s" action.', $this->dto->getName()));
        }

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
