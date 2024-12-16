<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Form\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\LockableInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\EventListener\LockVersionValidationListener;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class LockVersionValidationListenerTest extends TestCase
{
    public function testAddsErrorWhenSubmittedVersionDiffersFromStoredVersion(): void
    {
        $entity = $this->createLockableEntity(2);
        $form = $this->createRootForm($entity);
        $form->expects($this->once())->method('addError');

        $listener = $this->createListener($this->createContextProviderWithContext());
        $listener->onPreSubmit(new FormEvent($form, [EA::LOCK_VERSION => '1']));
    }

    public function testDoesNotAddErrorWhenVersionsMatch(): void
    {
        $entity = $this->createLockableEntity(3);
        $form = $this->createRootForm($entity);
        $form->expects($this->never())->method('addError');

        $listener = $this->createListener($this->createContextProviderWithContext());
        $listener->onPreSubmit(new FormEvent($form, [EA::LOCK_VERSION => '3']));
    }

    public function testIgnoresEntitiesThatAreNotLockable(): void
    {
        $form = $this->createRootForm(new \stdClass());
        $form->expects($this->never())->method('addError');

        $listener = $this->createListener($this->createContextProviderWithContext());
        $listener->onPreSubmit(new FormEvent($form, [EA::LOCK_VERSION => '1']));
    }

    public function testIgnoresSubmissionWithoutLockVersion(): void
    {
        $entity = $this->createLockableEntity(2);
        $form = $this->createRootForm($entity);
        $form->expects($this->never())->method('addError');

        $listener = $this->createListener($this->createContextProviderWithContext());
        $listener->onPreSubmit(new FormEvent($form, ['title' => 'foo']));
    }

    public function testIgnoresSubmissionOutsideAdminContext(): void
    {
        $entity = $this->createLockableEntity(2);
        $form = $this->createRootForm($entity);
        $form->expects($this->never())->method('addError');

        $contextProvider = $this->createMock(AdminContextProviderInterface::class);
        $contextProvider->method('getContext')->willReturn(null);

        $listener = $this->createListener($contextProvider);
        $listener->onPreSubmit(new FormEvent($form, [EA::LOCK_VERSION => '1']));
    }

    public function testIgnoresEntityWithoutStoredVersion(): void
    {
        $entity = $this->createLockableEntity(null);
        $form = $this->createRootForm($entity);
        $form->expects($this->never())->method('addError');

        $listener = $this->createListener($this->createContextProviderWithContext());
        $listener->onPreSubmit(new FormEvent($form, [EA::LOCK_VERSION => '1']));
    }

    private function createListener(AdminContextProviderInterface $contextProvider): LockVersionValidationListener
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('conflict message');

        return new LockVersionValidationListener(
            $translator,
            $contextProvider,
            $this->createMock(AdminUrlGeneratorInterface::class),
            new RequestStack(),
        );
    }

    private function createContextProviderWithContext(): AdminContextProviderInterface
    {
        $contextProvider = $this->createMock(AdminContextProviderInterface::class);
        $contextProvider->method('getContext')->willReturn($this->createMock(AdminContextInterface::class));

        return $contextProvider;
    }

    private function createRootForm(object $data): FormInterface&MockObject
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('isRoot')->willReturn(true);
        $form->method('getData')->willReturn($data);

        return $form;
    }

    private function createLockableEntity(?int $lockVersion): LockableInterface
    {
        return new class($lockVersion) implements LockableInterface {
            public function __construct(private ?int $lockVersion)
            {
            }

            public function getLockVersion(): ?int
            {
                return $this->lockVersion;
            }
        };
    }
}
