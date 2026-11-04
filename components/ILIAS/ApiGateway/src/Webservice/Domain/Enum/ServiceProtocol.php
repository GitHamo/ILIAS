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

namespace ILIAS\ApiGateway\Webservice\Domain\Enum;

enum ServiceProtocol: string
{
    case REST = 'rest';
    case SOAP = 'soap';
    case GraphQL = 'graphql';
    case gRPC = 'grpc';

    private const int REST_RATE_LIMIT_MAX_QUOTA = 100;
    private const int SOAP_RATE_LIMIT_MAX_QUOTA = 300;
    private const int GRAPHQL_RATE_LIMIT_MAX_QUOTA = 1000;
    private const int GRPC_RATE_LIMIT_MAX_QUOTA = 500;
    private const int GRAPHQL_RATE_LIMIT_UNITS_PER_REQUEST = 10; // GraphQL requests are typically more expensive, so we can assign more units per request
    private const int DEFAULT_RATE_LIMIT_UNITS_PER_REQUEST = 1;
    private const int DEFAULT_RATE_LIMIT_WINDOW_SECONDS = 60; // 1 minute

    /**
     * @return int recommended number of units consumed per request, used for rate limiting calculations
     */
    public function getRateLimitUnitsPerRequest(): int
    {
        return match ($this) {
            self::GraphQL => self::GRAPHQL_RATE_LIMIT_UNITS_PER_REQUEST,
            default => self::DEFAULT_RATE_LIMIT_UNITS_PER_REQUEST,
        };
    }

    /**
     * @return int recommended rate limit request quota per minute
     */
    public function getRateLimitMaxQuota(): int
    {
        return match ($this) {
            self::REST => self::REST_RATE_LIMIT_MAX_QUOTA, // req/min
            self::SOAP => self::SOAP_RATE_LIMIT_MAX_QUOTA, // req/min
            self::GraphQL => self::GRAPHQL_RATE_LIMIT_MAX_QUOTA, // cost/min (not requests)
            self::gRPC => self::GRPC_RATE_LIMIT_MAX_QUOTA, // req/min
        };
    }

    /**
     * @return int recommended time window for rate limiting in seconds
     */
    public function getRateLimitWindow(): int
    {
        return match ($this) {
            default => self::DEFAULT_RATE_LIMIT_WINDOW_SECONDS,
        };
    }
}
