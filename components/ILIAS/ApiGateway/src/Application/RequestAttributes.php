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

namespace ILIAS\ApiGateway\Application;

/**
 * Central registry for internal request attribute keys.
 *
 * @codeCoverageIgnore class is meant to hold constants that are used internally so it cannot be tested
 */
final readonly class RequestAttributes
{
    /**
     * Stores the authenticated AuthUser object.
     * Set by: AuthenticationMiddleware
     */
    public const string AUTH_USER = '_authenticated_user';

    /**
     * Stores the correlation ID for cross-layer request tracing.
     * Set by: RequestLoggingMiddleware
     */
    public const string CORRELATION_ID = '_correlation_id';

    /**
     * Stores the normalized (merged) request parameters (query + parsed body).
     * Set by: RequestParameterMiddleware
     */
    public const string PARSED_PARAMETERS = '_parsed_parameters';

    private function __construct()
    {
        // prevent accidental instantiation
    }
}
