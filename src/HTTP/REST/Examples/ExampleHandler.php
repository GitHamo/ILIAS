<?php

declare(strict_types=1);

namespace ILIAS\HTTP\REST\Examples;

use ILIAS\HTTP\REST\Attributes\Route;
use ILIAS\HTTP\REST\RouteHandler;
use InvalidArgumentException;
use JsonSerializable;
use Psr\Http\Message\ServerRequestInterface;

class ExampleHandler implements RouteHandler
{
    #[Route('GET', '/example/{id:[0-9]+}')]
    public function __invoke(ServerRequestInterface $request, array $args): JsonSerializable
    {
        $id = (int)$request->getAttribute('id');

        if ($id <= 0) {
            throw new InvalidArgumentException('ID must be a positive integer.');
        }

        // Example logic for handling the request
        return new class($id) implements JsonSerializable {
            public function __construct(private int $id) {}
            /**
             * @return array<string, string>
             */
            public function jsonSerialize(): array
            {
                return ['message' => "This is an example response and your ID is: {$this->id}"];
            }
        };
    }
}
