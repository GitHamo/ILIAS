<?php

declare(strict_types=1);

namespace ILIAS\HTTP\REST;

use JsonSerializable;
use Psr\Http\Message\ServerRequestInterface;

interface RouteHandler
{
    /**
     * @param array<string, mixed> $args Additional arguments for the handler.
     */
    public function __invoke(ServerRequestInterface $request, array $args): JsonSerializable;
}
