<?php

declare(strict_types=1);

namespace Tests\Unit\Webservice\Infrastructure;

use ILIAS\ApiGateway\Application\Middlewares;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use ILIAS\ApiGateway\Webservice\Infrastructure\RestWebservice;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RestWebserviceTest extends TestCase
{
    public function testHasAccessorToMiddlewares(): void
    {
        $monitoring = new ServiceMonitoring(false, false, false);
        $webservice = new RestWebservice($monitoring);

        $expected = [
            Middlewares\RequestParameterMiddleware::class,
            Middlewares\JsonBodyParserMiddleware::class,
            Middlewares\JsonContentNegotiationMiddleware::class,
        ];

        self::assertSame(
            $expected,
            $webservice->getMiddlewares(),
        );
    }

    public function testHandlesPayloadWithoutDebug(): void
    {
        $monitoring = new ServiceMonitoring(false, false, false);
        $webservice = new RestWebservice($monitoring);

        $actual = $webservice->transform(['key' => 'value']);

        $expected = '{"success":true,"data":{"key":"value"}}';

        self::assertSame(
            $expected,
            $actual->getBody(),
        );
    }

    public function testHandlesPayloadWithDebug(): void
    {
        $monitoring = new ServiceMonitoring(true, false, false);
        $webservice = new RestWebservice($monitoring);

        $actual = $webservice->transform(['key' => 'value']);

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
        $monitoring = new ServiceMonitoring(false, false, false);
        $webservice = new RestWebservice($monitoring);

        $expected = '{"success":true,"data":null}';

        $actual = $webservice->transform(null);

        self::assertSame(
            $expected,
            $actual->getBody(),
        );
    }

    public function testHandlesPayloadWithComplexData(): void
    {
        $monitoring = new ServiceMonitoring(false, false, false);
        $webservice = new RestWebservice($monitoring);

        $actual = $webservice->transform([
            'array' => [1, 2, 3],
            'object' => (object) ['key' => 'value'],
        ]);

        $expected = '{"success":true,"data":{"array":[1,2,3],"object":{"key":"value"}}}';

        self::assertSame(
            $expected,
            $actual->getBody(),
        );
    }

    public function testHandlesPayloadWithSpecialCharacters(): void
    {
        $monitoring = new ServiceMonitoring(false, false, false);
        $webservice = new RestWebservice($monitoring);

        $data = ['text' => 'Special characters: ñ, ü, 漢字'];

        $actual = $webservice->transform($data);

        $expected = '{"success":true,"data":{"text":"Special characters: \u00f1, \u00fc, \u6f22\u5b57"}}';

        self::assertSame(
            $expected,
            $actual->getBody(),
        );
    }

    public function testThrowsExceptionOnInvalidPayload(): void
    {
        $monitoring = new ServiceMonitoring(false, false, false);
        $webservice = new RestWebservice($monitoring);

        // We use a malformed UTF-8 string, which cannot be encoded by json_encode.
        $data = "\xB1\x31";

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Failed to encode payload data');

        $webservice->transform($data);
    }

    public function testHandlesErrorWithoutDetailsOrDebug(): void
    {
        $monitoring = new ServiceMonitoring(false, false, false);
        $webservice = new RestWebservice($monitoring);

        $exception = new RuntimeException('Something went wrong');

        $actual = $webservice->transformError($exception);

        $expected = '{"success":false,"error":"Something went wrong"}';

        self::assertSame(
            $expected,
            $actual->getBody(),
        );
    }

    public function testHandlesErrorWithDetails(): void
    {
        $monitoring = new ServiceMonitoring(true, true, true);
        $webservice = new RestWebservice($monitoring);

        $exception = new RuntimeException('Something went wrong');

        $actual = $webservice->transformError($exception);
        /**
         * @var array<string, mixed>
         */
        $actualBody = json_decode($actual->getBody(), true);

        self::assertFalse($actualBody['success']);
        self::assertSame('Something went wrong', $actualBody['error']);
        self::assertIsArray($actualBody['stack']);
        self::assertNotEmpty($actualBody['stack']);
    }

    public function testHandlesErrorWithDebug(): void
    {
        $monitoring = new ServiceMonitoring(true, true, false);
        $webservice = new RestWebservice($monitoring);

        $exception = new RuntimeException('Something went wrong');

        $actual = $webservice->transformError($exception);

        $expected = <<<JSON
{
    "success": false,
    "error": "Something went wrong"
}
JSON;

        self::assertSame(
            $expected,
            $actual->getBody(),
        );
    }

    public function testHandlesErrorWithDetailsAndDebug(): void
    {
        $monitoring = new ServiceMonitoring(true, true, true);
        $webservice = new RestWebservice($monitoring);

        $exception = new RuntimeException('Something went wrong');

        $actual = $webservice->transformError($exception);
        /**
         * @var array<string, mixed>
         */
        $actualBody = json_decode($actual->getBody(), true);

        self::assertFalse($actualBody['success']);
        self::assertSame('Something went wrong', $actualBody['error']);
        self::assertIsArray($actualBody['stack']);
        self::assertNotEmpty($actualBody['stack']);
        self::assertStringContainsString("\n", $actual->getBody());
    }

    public function testHandlesErrorWithSpecialCharacters(): void
    {
        $monitoring = new ServiceMonitoring(false, false, false);
        $webservice = new RestWebservice($monitoring);

        $exception = new RuntimeException('Error: ñ, ü, 漢字');

        $actual = $webservice->transformError($exception);

        $expected = '{"success":false,"error":"Error: ñ, ü, 漢字"}';

        self::assertSame(
            $expected,
            $actual->getBody(),
        );
    }

    public function testHandlesErrorWithSlashes(): void
    {
        $monitoring = new ServiceMonitoring(false, false, false);
        $webservice = new RestWebservice($monitoring);

        $exception = new RuntimeException('Error: path/to/file');

        $actual = $webservice->transformError($exception);

        $expected = '{"success":false,"error":"Error: path/to/file"}';

        self::assertSame(
            $expected,
            $actual->getBody(),
        );
    }

    public function testHandleReturnsCorrectHeaders(): void
    {
        $monitoring = new ServiceMonitoring(false, false, false);
        $webservice = new RestWebservice($monitoring);

        $payload = $webservice->transform([]);
        $actual = $payload->getHeaders();

        $expected = [
            'Content-Type' => 'application/json',
        ];

        self::assertSame($expected, $actual);
    }

    public function testHandleErrorReturnsCorrectHeaders(): void
    {
        $monitoring = new ServiceMonitoring(false, false, false);
        $webservice = new RestWebservice($monitoring);

        $exception = new RuntimeException('Something went wrong');

        $payload = $webservice->transformError($exception);
        $actual = $payload->getHeaders();

        $expected = [
            'Content-Type' => 'application/json',
        ];

        self::assertSame($expected, $actual);
    }
}
