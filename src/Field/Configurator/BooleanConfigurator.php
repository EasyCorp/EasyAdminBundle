<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class BooleanConfigurator implements FieldConfiguratorInterface
{
    public function __construct(
        private AdminUrlGeneratorInterface $adminUrlGenerator,
        private AuthorizationCheckerInterface $authChecker,
        private ?CsrfTokenManagerInterface $csrfTokenManager = null,
    ) {
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return BooleanField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $isRenderedAsSwitch = true === $field->getCustomOption(BooleanField::OPTION_RENDER_AS_SWITCH);

        if ($isRenderedAsSwitch) {
            $crudDto = $context->getCrud();

            $canBeToggled = $this->authChecker->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => Action::EDIT, 'entity' => $entityDto])
                && (null === $crudDto || $this->canSaveEditForm($crudDto, $entityDto));

            if ($canBeToggled && null !== $crudDto && null !== $entityDto->getPrimaryKeyValue()) {
                $toggleUrl = $this->adminUrlGenerator
                    ->setController($crudDto->getControllerFqcn())
                    ->setAction(Action::EDIT)
                    ->setEntityId($entityDto->getPrimaryKeyValue())
                    ->set('fieldName', $field->getProperty())
                    // the token ID includes the CRUD controller and the property, so the token
                    // of a switch is not valid for other fields or other CRUD controllers
                    ->set('csrfToken', $this->csrfTokenManager?->getToken(BooleanField::CSRF_TOKEN_NAME.'-'.$crudDto->getControllerFqcn().'-'.$field->getProperty()))
                    ->generateUrl();
                $field->setCustomOption(BooleanField::OPTION_TOGGLE_URL, $toggleUrl);
            }

            if (!$canBeToggled && Action::INDEX === $crudDto->getCurrentAction()) {
                $field->setFormTypeOptionIfNotSet('disabled', true);
            }

            $labelClass = 'checkbox-switch';
            if (null !== ($switchVariant = $field->getCustomOption(BooleanField::OPTION_SWITCH_VARIANT))) {
                $labelClass .= ' checkbox-switch-'.$switchVariant;
            }
            $field->setFormTypeOptionIfNotSet('label_attr.class', $labelClass);
            $field->setCssClass($field->getCssClass().' has-switch');
        }
    }

    /**
     * The switch saves the entity through the 'edit' action, so it follows the same rules as
     * AbstractCrudController::edit(). A form disabled in an overridden createEditFormBuilder()
     * can't be detected without building it for every row; the controller rejects those toggles.
     */
    private function canSaveEditForm(CrudDto $crudDto, EntityDto $entityDto): bool
    {
        if ((bool) $crudDto->getEditFormOptions()->get('disabled')) {
            return false;
        }

        $saveActionNames = $crudDto->getActionsConfig()->getSaveActionNames(Crud::PAGE_EDIT);
        foreach ($saveActionNames as $actionName) {
            if ($this->authChecker->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => $actionName, 'entity' => $entityDto])) {
                return true;
            }
        }

        return [] === $saveActionNames;
    }
}
