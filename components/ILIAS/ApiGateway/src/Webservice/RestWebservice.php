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

namespace ILIAS\ApiGateway\Webservice;

use ILIAS\ApiGateway\Configuration\WebConfig;
use ILIAS\ApiGateway\Models\Payload;
use ILIAS\ApiGateway\ServiceProtocol;
use ILIAS\ApiGateway\Webservice;
use Override;
use RuntimeException;
use Throwable;

readonly class RestWebservice implements Webservice
{
    public function __construct(
        private WebConfig $config,
    ) {}

    #[Override]
    public function getProtocol(): ServiceProtocol
    {
        return ServiceProtocol::REST;
    }

    #[Override]
    public function handle(Payload $payload): Payload
    {
        // throw new RuntimeException('Not implemented');
        $payloadData = [
            'success' => true,
            'data' => $payload->getData(),
        ];

        $jsonFlags = $this->config->debugMode ? JSON_PRETTY_PRINT : 0;

        return $this->createPayload($payloadData, $jsonFlags);
    }

    #[Override]
    public function handleError(Throwable $exception): Payload
    {
        $payloadData = [
            'success' => false,
            'error' => $exception->getMessage(),
        ];

        if ($this->config->logErrorDetails) {
            $payloadData['stack'] = $exception->getTrace();
        }

        $jsonFlags = $this->config->debugMode ? JSON_PRETTY_PRINT : 0;
        $jsonFlags |= JSON_UNESCAPED_SLASHES;
        $jsonFlags |= JSON_UNESCAPED_UNICODE;

        return $this->createPayload($payloadData, $jsonFlags);
    }

    private function createPayload(mixed $data, int $jsonFlags): Payload
    {
        $body = json_encode($data, $jsonFlags);

        if ($body === false) {
            throw new RuntimeException('Failed to encode payload data');
        }

        return new Payload(
            $data,
            [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($body),
            ],
            $body,
        );
    }
}
