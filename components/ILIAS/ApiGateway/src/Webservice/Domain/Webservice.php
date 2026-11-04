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

namespace ILIAS\ApiGateway\Webservice\Domain;

use ILIAS\ApiGateway\Webservice\Domain\Model\Payload;
use Throwable;

/**
 * The webservice acts as a formatting layer that transforms raw data into payloads.
 * It encapsulates protocol requirements, mandatory middlewares, and error presentation.
 */
interface Webservice
{
    /**
     * Returns the stack of middlewares required to process this protocol.
     *
     * @return string[]
     */
    public function getMiddlewares(): array;

    /**
     * Transforms raw data into a protocol-specific Payload.
     */
    public function transform(mixed $data): Payload;

    /**
     * Transforms an exception into a protocol-specific Error Payload.
     */
    public function transformError(Throwable $exception): Payload;
}
