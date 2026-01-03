<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\ApiGateway\Routes;

use Closure;
use ILIAS\ApiGateway\Auth\Domain\Model\AuthUser;
use Override;

readonly class ApiRoute implements Route
{
    /**
     * @param string[] $methods
     * @param string[] $middlewares
     */
    public function __construct(
        private string $name,
        private string $path,
        private array $methods,
        private string $description,
        private Closure $handler,
        private array $middlewares = [],
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    #[Override]
    public function getPath(): string
    {
        return $this->path;
    }

    #[Override]
    public function getMethods(): array
    {
        return $this->methods;
    }

    #[Override]
    public function getHandler(): RouteHandler
    {
        return new class($this->handler) implements RouteHandler {
            public function __construct(private Closure $handle) {}

            #[Override]
            public function __invoke(array $params, ?AuthUser $user)
            {
                return ($this->handle)($params, $user);
            }
        };
    }

    #[Override]
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
