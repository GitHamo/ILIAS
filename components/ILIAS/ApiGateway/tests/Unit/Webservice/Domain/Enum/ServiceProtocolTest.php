<?php

declare(strict_types=1);

namespace Tests\Unit\Webservice\Domain\Enum;

use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServiceProtocol::class)]
class ServiceProtocolTest extends TestCase
{
    #[DataProvider('rateLimitUnitsPerRequestDataProvider')]
    public function testRateLimitUnitsPerRequest(ServiceProtocol $protocol, int $expected): void
    {
        $actual = $protocol->getRateLimitUnitsPerRequest();

        self::assertEquals($expected, $actual);
    }

    /**
     * @return array<string, array{ServiceProtocol, int}>
     */
    public static function rateLimitUnitsPerRequestDataProvider(): array
    {
        return [
            "REST protocol" => [ServiceProtocol::REST, 1],
            "SOAP protocol" => [ServiceProtocol::SOAP, 1],
            "GraphQL protocol" => [ServiceProtocol::GraphQL, 10],
            "gRPC protocol" => [ServiceProtocol::gRPC, 1],
        ];
    }

    #[DataProvider('rateLimitMaxQuotasDataProvider')]
    public function testRateLimitMaxQuota(ServiceProtocol $protocol, int $expected): void
    {
        $actual = $protocol->getRateLimitMaxQuota();

        self::assertEquals($expected, $actual);
    }

    /**
     * @return array<string, array{ServiceProtocol, int}>
     */
    public static function rateLimitMaxQuotasDataProvider(): array
    {
        return [
            "REST protocol" => [ServiceProtocol::REST, 100],
            "SOAP protocol" => [ServiceProtocol::SOAP, 300],
            "GraphQL protocol" => [ServiceProtocol::GraphQL, 1000],
            "gRPC protocol" => [ServiceProtocol::gRPC, 500],
        ];
    }

    #[DataProvider('rateLimitWindowDataProvider')]
    public function testRateLimitWindow(ServiceProtocol $protocol, int $expected): void
    {
        $actual = $protocol->getRateLimitWindow();

        self::assertEquals($expected, $actual);
    }

    /**
     * @return array<string, array{ServiceProtocol, int}>
     */
    public static function rateLimitWindowDataProvider(): array
    {
        return [
            "REST protocol" => [ServiceProtocol::REST, 60],
            "SOAP protocol" => [ServiceProtocol::SOAP, 60],
            "GraphQL protocol" => [ServiceProtocol::GraphQL, 60],
            "gRPC protocol" => [ServiceProtocol::gRPC, 60],
        ];
    }
}
