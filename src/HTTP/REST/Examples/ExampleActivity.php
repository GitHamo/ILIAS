<?php

declare(strict_types=1);

namespace ILIAS\HTTP\REST\Examples;

use ILIAS\HTTP\REST\Attributes\Route;
use ILIAS\HTTP\REST\RouteHandler;
use JsonSerializable;
use Psr\Http\Message\ServerRequestInterface;

class ExampleActivity implements RouteHandler, Activity
{
    public function perform(mixed $parameters): mixed
    {
        // Implement the activity logic here
        // For example, you might want to return some processed data based on the parameters
        return ['status' => 'Activity performed successfully', 'parameters' => $parameters];
    }

    #[Route('GET', '/activity')]
    public function __invoke(ServerRequestInterface $request, array $args): JsonSerializable
    {
        $params = $request->getQueryParams();

        $results = $this->perform($params);

        return new class($results) implements JsonSerializable {
            public function __construct(private array $results) {}
            /**
             * @return array<string, string>
             */
            public function jsonSerialize(): array
            {
                return $this->results;
            }
        };
    }
}

interface Activity
{
    public function perform(mixed $parameters): mixed;
}
