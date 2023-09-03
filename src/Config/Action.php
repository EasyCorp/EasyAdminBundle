<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDtoInterface;
use function Symfony\Component\String\u;
use Symfony\Contracts\Translation\TranslatableInterface;

final class Action implements ActionInterface
{
    private ActionDtoInterface $dto;

    private function __construct(ActionDtoInterface $actionDto)
    {
        $this->dto = $actionDto;
    }

    public function __toString()
    {
        return $this->dto->getName();
    }

    public static function new(string $name, $label = null, ?string $icon = null): self
    {
        if (!\is_string($label)
            && !$label instanceof TranslatableInterface
            && false !== $label
            && null !== $label) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$label',
                __METHOD__,
                sprintf('"%s", "string", "false" or "null"', TranslatableInterface::class),
                \gettype($label)
            );
        }

        $dto = new ActionDto();
        $dto->setType(self::TYPE_ENTITY);
        $dto->setName($name);
        $dto->setLabel($label ?? self::humanizeString($name));
        $dto->setIcon($icon);
        $dto->setHtmlElement('a');
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

    public function setLabel($label): self
    {
        if (!\is_string($label)
            && !$label instanceof TranslatableInterface
            && false !== $label
            && null !== $label) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$label',
                __METHOD__,
                '"string", "false" or "null"',
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

    public function setCssClass(string $cssClass): self
    {
        $this->dto->setCssClass(trim($cssClass));

        return $this;
    }

    public function addCssClass(string $cssClass): self
    {
        $this->dto->setAddedCssClass(trim($cssClass));

        return $this;
    }

    public function displayAsLink(): self
    {
        $this->dto->setHtmlElement('a');

        return $this;
    }

    public function displayAsButton(): self
    {
        $this->dto->setHtmlElement('button');

        return $this;
    }

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

    public function linkToRoute(string $routeName, array|callable $routeParameters = []): self
    {
        $this->dto->setRouteName($routeName);
        $this->dto->setRouteParameters($routeParameters);

        return $this;
    }

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

    public function getAsDto(): ActionDtoInterface
    {
        if (null === $this->dto->getLabel() && null === $this->dto->getIcon()) {
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
