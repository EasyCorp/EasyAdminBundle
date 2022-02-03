<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use Symfony\Component\HttpFoundation\Response;

/**
 * These methods allow to stop the event propagation and return the given
 * response immediately.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
trait StoppableEventTrait
{
    private ?Response $response = null;

    public function isPropagationStopped(): bool
    {
        return null !== $this->response;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}
