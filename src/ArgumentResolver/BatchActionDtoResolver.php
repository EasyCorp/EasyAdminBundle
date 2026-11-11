<?php

namespace EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/*
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class BatchActionDtoResolver implements ValueResolverInterface
{
    public function __construct(private AdminContextProviderInterface $adminContextProvider)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (BatchActionDto::class !== $argument->getType()) {
            return [];
        }

        if (null === $context = $this->adminContextProvider->getContext()) {
            throw new \RuntimeException(sprintf('Some of your controller actions have type-hinted an argument with the "%s" class but that\'s only available for actions run to serve EasyAdmin requests. Remove the type-hint or make sure the action is part of an EasyAdmin request.', BatchActionDto::class));
        }

        // these methods throw a "bad request" exception when the type of the submitted value is wrong
        $input = $context->getRequest()->request;
        $name = $input->getString(EA::BATCH_ACTION_NAME);
        $entityIds = $input->all(EA::BATCH_ACTION_ENTITY_IDS);
        $entityFqcn = $input->getString(EA::ENTITY_FQCN);
        $csrfToken = $input->getString(EA::BATCH_ACTION_CSRF_TOKEN);

        $nonScalarEntityIds = array_filter($entityIds, static fn (mixed $entityId): bool => !\is_scalar($entityId));
        if ('' === $name || '' === $entityFqcn || '' === $csrfToken || [] !== $nonScalarEntityIds) {
            throw new BadRequestHttpException('The batch action request must include the action name, the entity FQCN, the CSRF token and a list of scalar entity IDs.');
        }

        yield new BatchActionDto($name, $entityIds, $entityFqcn, $csrfToken);
    }
}
