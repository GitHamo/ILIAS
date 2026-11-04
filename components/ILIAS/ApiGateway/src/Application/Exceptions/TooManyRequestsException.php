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

namespace ILIAS\ApiGateway\Application\Exceptions;

use ILIAS\HTTP\StatusCode;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Throwable;

/**
 * Thrown when a client exceeds the configured request rate limit.
 * This automatically results in a 429 Too Many Requests response.
 */
final class TooManyRequestsException extends HttpException
{
    public function __construct(
        ServerRequestInterface $request,
        string $message = 'Too many requests.',
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $request,
            $message,
            StatusCode::HTTP_TOO_MANY_REQUESTS,
            $previous,
        );
    }
}
