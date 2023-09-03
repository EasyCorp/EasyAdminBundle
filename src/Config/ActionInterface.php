<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Config;


use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDtoInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface ActionInterface
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

    /**
     * @param TranslatableInterface|string|false|null $label Use FALSE to hide the label; use NULL to autogenerate it
     * @param string|null                             $icon  The full CSS classes of the FontAwesome icon to render (see https://fontawesome.com/v6/search?m=free)
     */
    public static function new(string $name, $label = null, ?string $icon = null): self;

    public function createAsGlobalAction(): Action;

    public function createAsBatchAction(): Action;

    /**
     * @param TranslatableInterface|string|false|null $label Use FALSE to hide the label; use NULL to autogenerate it
     */
    public function setLabel($label): Action;

    public function setIcon(?string $icon): Action;

    /**
     * Use this to override the default CSS classes applied to actions and use instead your own CSS classes.
     * See also addCssClass() to add your own custom classes without removing the default ones.
     */
    public function setCssClass(string $cssClass): Action;

    /**
     * This adds the given CSS class(es) to the classes already applied to the actions
     * (no matter if they are the default ones or some custom CSS classes set with the setCssClass() method).
     */
    public function addCssClass(string $cssClass): Action;

    public function displayAsLink(): Action;

    public function displayAsButton(): Action;

    public function setHtmlAttributes(array $attributes): Action;

    public function setTemplatePath(string $templatePath): Action;

    public function linkToCrudAction(string $crudActionName): Action;

    /**
     * @param array|callable $routeParameters The callable has the signature: function ($entity): array
     *
     * Route parameters can be defined as a callable with the signature: function ($entityInstance): array
     * Example: ->linkToRoute('invoice_send', fn (Invoice $entity) => ['uuid' => $entity->getId()]);
     */
    public function linkToRoute(
        string $routeName,
        array|callable $routeParameters = []
    ): Action;

    /**
     * @param string|callable $url
     */
    public function linkToUrl($url): Action;

    public function setTranslationParameters(array $parameters): Action;

    public function displayIf(callable $callable): Action;

    public function getAsDto(): ActionDtoInterface;
}
