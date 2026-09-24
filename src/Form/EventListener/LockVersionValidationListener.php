<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Form\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\LockableInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Ahmed EBEN HASSINE <ahmedbhs123@gmail.com>
 */
class LockVersionValidationListener implements EventSubscriberInterface
{
    private const CONFLICT_ATTRIBUTE = '_ea_lock_version_conflict';

    private TranslatorInterface $translator;
    private AdminContextProviderInterface $adminContextProvider;
    private AdminUrlGeneratorInterface $adminUrlGenerator;
    private RequestStack $requestStack;

    public function __construct(
        TranslatorInterface $translator,
        AdminContextProviderInterface $adminContextProvider,
        AdminUrlGeneratorInterface $adminUrlGenerator,
        RequestStack $requestStack,
    ) {
        $this->translator = $translator;
        $this->adminContextProvider = $adminContextProvider;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->requestStack = $requestStack;
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
            AfterCrudActionEvent::class => 'onAfterCrudAction',
        ];
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();
        $instance = $form->getData();

        if (!($form->isRoot() && $instance instanceof LockableInterface)) {
            return;
        }

        $submittedLockVersion = $data[EA::LOCK_VERSION] ?? null;
        if (null === $submittedLockVersion) {
            return;
        }

        if (null === $this->adminContextProvider->getContext()) {
            return;
        }

        $currentLockVersion = $instance->getLockVersion();
        if (null === $currentLockVersion) {
            return;
        }

        if ((int) $submittedLockVersion === $currentLockVersion) {
            return;
        }

        $form->addError(new FormError(
            $this->translator->trans('flash_lock_error.message', [], 'EasyAdminBundle')
        ));

        $this->requestStack->getCurrentRequest()?->attributes->set(self::CONFLICT_ATTRIBUTE, true);
    }

    public function onAfterCrudAction(AfterCrudActionEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || true !== $request->attributes->get(self::CONFLICT_ATTRIBUTE)) {
            return;
        }

        $context = $event->getAdminContext();
        if (null === $context || null === $context->getCrud()) {
            return;
        }

        $session = $request->getSession();
        if ($session instanceof Session) {
            $session->getFlashBag()->add(
                'warning',
                $this->translator->trans('flash_lock_error.message', [], 'EasyAdminBundle')
            );
        }

        $url = $this->adminUrlGenerator
            ->setDashboard($context->getDashboardControllerFqcn())
            ->setController($context->getCrud()->getControllerFqcn())
            ->setAction(Action::EDIT)
            ->setEntityId($context->getEntity()->getPrimaryKeyValue())
            ->generateUrl();

        $event->setResponse(new RedirectResponse($url));
    }
}
