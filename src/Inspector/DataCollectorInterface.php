<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Inspector;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Collects information about the requests related to EasyAdmin and displays
 * it both in the web debug toolbar and in the profiler.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface DataCollectorInterface
{
    public function reset(): void;

    public function collect(Request $request, Response $response, $exception = null): void;

    public function isEasyAdminRequest(): bool;

    public function getData(): array;

    public function getName(): string;
}
