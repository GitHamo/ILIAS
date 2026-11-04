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

namespace ILIAS\ApiGateway\Application\Factories;

use ILIAS\ApiGateway\Configuration\Domain\Model\AuthConfig;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceAccess;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceIdentity;
use ILIAS\ApiGateway\RateLimiting\Domain\Model\RateLimit;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class RateLimitFactory
{
    private const string CLIENT_IDENTIFIER_DELIMITER = '|';

    public function __construct(
        private ServiceIdentity $identity,
        private ServiceAccess $access,
        private AuthConfig $authConfig,
    ) {
    }

    public function createFromRequest(
        Request $request,
    ): RateLimit {
        $protocol = $this->identity->protocol;
        $clientIdentifier = $this->createClientIdentifier($request);
        $endpointIdentifier = $this->createEndpointIdentifier($request);
        $windowSeconds = $this->access->rateLimitWindow;
        $windowSeconds = $windowSeconds < 1 ? $protocol->getRateLimitWindow() : $windowSeconds;
        $maxUnits = $this->access->rateLimitQuota;
        $maxUnits = $maxUnits < 1 ? $protocol->getRateLimitMaxQuota() : $maxUnits;

        return new RateLimit(
            fingerprint: $clientIdentifier,
            action: $endpointIdentifier,
            windowSeconds: $windowSeconds,
            maxUnits: $maxUnits,
        );
    }

    private function createClientIdentifier(Request $request): string
    {
        $hashAlgo = $this->authConfig->getHashAlgo();
        $serverParams = $request->getServerParams();
        // Check X-Forwarded-For if configured, otherwise fall back to REMOTE_ADDR
        $ip = $serverParams['HTTP_X_FORWARDED_FOR']
            ?? $serverParams['REMOTE_ADDR']
            ?? 'unknown';
        // If X-Forwarded-For contains multiple IPs, use the first (original client)
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }
        $userAgent = $request->getHeaderLine('User-Agent') ?: 'unknown';

        $data = implode(
            self::CLIENT_IDENTIFIER_DELIMITER,
            [
                $ip,
                $userAgent,
            ],
        );

        return hash_hmac(
            algo: $hashAlgo->value,
            data: $data,
            key: $this->authConfig->getSecretKey(),
        );
    }

    private function createEndpointIdentifier(Request $request): string
    {
        return \sprintf(
            '%s %s',
            $request->getMethod(),
            $request->getUri()->getPath(),
        );
    }
}
