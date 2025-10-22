<?php

declare(strict_types=1);

namespace Tests\Unit\Webservices;

use ILIAS\ApiGateway\Models\Payload;
use ILIAS\ApiGateway\ServiceProtocol;
use ILIAS\ApiGateway\Webservices\RestWebservice;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RestWebserviceTest extends TestCase
{
    private RestWebservice $webservice;
    private string $basePath = '/api/v1';
    private bool $isEnabled = true;
    private bool $isDebugEnabled = false;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->webservice = new RestWebservice(
            $this->basePath,
            $this->isEnabled,
            $this->isDebugEnabled,
        );
    }

    public function testHasAccessorToProtocol(): void
    {
        self::assertSame(
            ServiceProtocol::REST,
            $this->webservice->getProtocol(),
        );
    }

    public function testHasAccessorToBasePath(): void
    {
        self::assertSame(
            $this->basePath,
            $this->webservice->getBasePath(),
        );
    }

    public function testIsEnabled(): void
    {
        self::assertSame(
            $this->isEnabled,
            $this->webservice->isEnabled(),
        );

        self::assertSame(
            false,
            (new RestWebservice($this->basePath, false, $this->isDebugEnabled))->isEnabled(),
        );
    }

    public function testHandlesPayloadWithoutDebug(): void
    {
        $payloadData = ['key' => 'value'];
        $payload = new Payload($payloadData);

        $actual = $this->webservice->handle($payload);

        $expected = '{"success":true,"data":{"key":"value"}}';

        self::assertSame(
            $expected,
            $actual->getBody(),
        );
    }

    public function testHandlesPayloadWithDebug(): void
    {
        $payloadData = ['key' => 'value'];
        $payload = new Payload($payloadData);

        $webservice = new RestWebservice(
            $this->basePath,
            $this->isEnabled,
            true,
        );

        $actual = $webservice->handle($payload);

        $expected = <<<JSON
{
    "success": true,
    "data": {
        "key": "value"
    }
}
JSON;

        self::assertSame(
            $expected,
            $actual->getBody(),
        );
    }

    public function testHandlesEmptyPayload(): void
    {
        $payload = new Payload();

        $actual = $this->webservice->handle($payload);

        $expected = '{"success":true,"data":null}';

        self::assertSame(
            $expected,
            $actual->getBody(),
        );
    }

    public function testHandlesPayloadWithComplexData(): void
    {
        $payloadData = [
            'array' => [1, 2, 3],
            'object' => (object)['key' => 'value'],
        ];
        $payload = new Payload($payloadData);

        $actual = $this->webservice->handle($payload);

        $expected = '{"success":true,"data":{"array":[1,2,3],"object":{"key":"value"}}}';

        self::assertSame(
            $expected,
            $actual->getBody(),
        );
    }

    public function testHandlesPayloadWithSpecialCharacters(): void
    {
        $payloadData = ['text' => 'Special characters: ñ, ü, 漢字'];
        $payload = new Payload($payloadData);

        $actual = $this->webservice->handle($payload);

        $expected = '{"success":true,"data":{"text":"Special characters: \u00f1, \u00fc, \u6f22\u5b57"}}';

        self::assertSame(
            $expected,
            $actual->getBody(),
        );
    }

    public function testThrowsExceptionOnInvalidPayload(): void
    {
        $payload = new Payload("\xB1\x31");

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Failed to encode payload');

        $this->webservice->handle($payload);
    }
}
