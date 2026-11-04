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

namespace ILIAS\ApiGateway\Routing;

use ILIAS\ApiGateway\ServiceContract\Model\Schema;

interface Route
{
    /**
     * @return string Unique business identifier for the route
     */
    public function getName(): string;

    /**
     * @return string Human readable description
     */
    public function getDescription(): string;

    /**
     * @return string URL path this route responds to, e.g. '/users/{id}'
     */
    public function getPath(): string;

    /**
     * @return HttpMethod HTTP method this route responds to, e.g. HttpMethod::GET or HttpMethod::POST
     */
    public function getMethod(): HttpMethod;

    /**
     * @return Action action holding the business logic
     */
    public function getAction(): Action;

    /**
     * @return Schema|null The input schema or null if none
     */
    public function getInput(): ?Schema;

    /**
     * @return Schema|null The output schema or null if none
     */
    public function getOutput(): ?Schema;

    /**
     * @return array<string> list of namespaces of middleware classes
     */
    public function getMiddlewares(): array;
}
