<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use Symfony\Component\HttpFoundation\Response;

/**
 * The methods of this class allow to stop the event propagation and
 * return the given response immediately.
 */
class StoppableEvent
{
    private $response;

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
