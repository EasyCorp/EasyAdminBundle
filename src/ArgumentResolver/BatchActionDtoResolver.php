<?php

namespace EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class BatchActionDtoResolver implements ArgumentValueResolverInterface
{
    private $adminContextProvider;
    private $adminUrlGenerator;

    public function __construct(AdminContextProvider $adminContextProvider, AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return BatchActionDto::class === $argument->getType();
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (null === $context = $this->adminContextProvider->getContext()) {
            throw new \RuntimeException(sprintf('Some of your controller actions have type-hinted an argument with the "%s" class but that\'s only available for actions run to serve EasyAdmin requests. Remove the type-hint or make sure the action is part of an EasyAdmin request.', BatchActionDto::class));
        }

        $batchActionUrl = $context->getRequest()->request->get(EA::BATCH_ACTION_URL);
        $batchActionUrlQueryString = parse_url($batchActionUrl, \PHP_URL_QUERY);
        parse_str($batchActionUrlQueryString, $batchActionUrlParts);
        $referrerUrl = $batchActionUrlParts[EA::REFERRER] ?? $this->adminUrlGenerator->unsetAll()->generateUrl();

        yield new BatchActionDto(
            $context->getRequest()->request->get(EA::BATCH_ACTION_NAME),
            $context->getRequest()->request->all()[EA::BATCH_ACTION_ENTITY_IDS] ?: [],
            $context->getRequest()->request->get(EA::ENTITY_FQCN),
            $referrerUrl,
            $context->getRequest()->request->get(EA::BATCH_ACTION_CSRF_TOKEN)
        );
    }
}
