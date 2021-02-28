<?php

namespace EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class BatchActionDtoResolver implements ArgumentValueResolverInterface
{
    private $adminContextProvider;

    public function __construct(AdminContextProvider $adminContextProvider)
    {
        $this->adminContextProvider = $adminContextProvider;
    }

    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return BatchActionDto::class === $argument->getType();
    }

    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        if (null === $context = $this->adminContextProvider->getContext()) {
            throw new \RuntimeException(sprintf('Some of your controller actions have type-hinted an argument with the "%s" class but that\'s only available for actions run to serve EasyAdmin requests. Remove the type-hint or make sure the action is part of an EasyAdmin request.', BatchActionDto::class));
        }

        yield new BatchActionDto(
            $context->getCrud()->getCurrentAction(),
            $context->getRequest()->request->get(EA::BATCH_ENTITY_IDS, []),
            $context->getCrud()->getEntityFqcn(),
            $context->getReferrer()
        );
    }
}
