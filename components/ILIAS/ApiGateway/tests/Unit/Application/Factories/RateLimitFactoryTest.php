<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Factories;

use ILIAS\ApiGateway\Application\Factories\RateLimitFactory;
use ILIAS\ApiGateway\Configuration\Domain\Enum\HashAlgo;
use ILIAS\ApiGateway\Configuration\Domain\Model\AuthConfig;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceAccess;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceIdentity;
use ILIAS\ApiGateway\RateLimiting\Domain\Model\RateLimit;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

#[CoversClass(RateLimitFactory::class)]
class RateLimitFactoryTest extends TestCase
{
    private RateLimitFactory $factory;
    private ServiceProtocol $serviceProtocol;

    #[\Override]
    protected function setUp(): void
    {
        $this->serviceProtocol = ServiceProtocol::REST;

        $identity = new ServiceIdentity($this->serviceProtocol, 'http://localhost', $this->serviceProtocol->value);
        $access = new ServiceAccess(true, true, 1, 100, 60);

        $authConfig = $this->createConfiguredMock(AuthConfig::class, [
            'getSecretKey' => 'test-secret-key',
            'getHashAlgo' => HashAlgo::SHA256,
        ]);

        $this->factory = new RateLimitFactory($identity, $access, $authConfig);
    }

    public function testParsesRateLimitFingerprintFromRequest(): void
    {
        $requestMock = $this->createConfiguredMock(Request::class, [
            'getServerParams' => ['REMOTE_ADDR' => '127.0.0.1'],
            'getHeaderLine' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/237.84.2.178 Safari/537.36',
        ]);

        $expected = $this->createRateLimit(
            fingerprint: '776d1f8dda2fb315421de139e822ce794bd9b01d09fff110e6a6de3738acac4b',
            windowSeconds: 60,
            maxUnits: 100,
        );

        $actual = $this->factory->createFromRequest($requestMock);

        $this->assertEquals($expected, $actual);
    }

    public function testParsesRateLimitFingerprintWithDefaultValues(): void
    {
        $requestMock = $this->createMock(Request::class);

        $expected = $this->createRateLimit(
            windowSeconds: 60,
            maxUnits: 100,
        );

        $actual = $this->factory->createFromRequest($requestMock);

        $this->assertEquals($expected, $actual);
    }

    public function testParsesRateLimitActionFromRequest(): void
    {
        $requestMock = $this->createConfiguredMock(Request::class, [
            'getMethod' => $method = 'POST',
            'getUri' => $this->createConfiguredMock(UriInterface::class, [
                'getPath' => $path = '/api/resource',
            ]),
        ]);

        $expected = $this->createRateLimit(
            action: "$method $path",
            windowSeconds: 60,
            maxUnits: 100,
        );

        $actual = $this->factory->createFromRequest($requestMock);

        $this->assertEquals($expected, $actual);
    }

    public function testCreateClientIdentifierUsesXForwardedForWhenAvailable(): void
    {
        $requestMock = $this->createConfiguredMock(Request::class, [
            'getServerParams' => [
                'REMOTE_ADDR' => '192.168.1.1', // Proxy IP
                'HTTP_X_FORWARDED_FOR' => '10.0.0.1, 192.168.1.1', // Client IP, Proxy IP
            ],
            'getHeaderLine' => 'Mozilla/5.0 (Test)',
        ]);

        $expectedFingerprint = '4b315ffa8d779800132d16b238e53a860a54470e6bd90ee49bae37abf8b34a44';

        $actualRateLimit = $this->factory->createFromRequest($requestMock);

        $this->assertSame($expectedFingerprint, $actualRateLimit->fingerprint);
    }

    public function testCreateClientIdentifierFallsBackToRemoteAddr(): void
    {
        $requestMock = $this->createConfiguredMock(Request::class, [
            'getServerParams' => [
                'REMOTE_ADDR' => '127.0.0.1',
            ],
            'getHeaderLine' => 'Mozilla/5.0 (Test)',
        ]);

        $expectedFingerprint = '99ef8da68fa282ddfba57438e11be705c492312f8d6f69e5d4d6a0a30ac8f091';

        $actualRateLimit = $this->factory->createFromRequest($requestMock);

        $this->assertSame($expectedFingerprint, $actualRateLimit->fingerprint);
    }

    #[DataProvider('invalidRateLimitValuesProvider')]
    public function testCreateFromRequestFallsBackToProtocolDefaultsForInvalidValues(int $invalidValue): void
    {
        $identity = new ServiceIdentity($this->serviceProtocol, 'http://localhost', $this->serviceProtocol->value);
        $access = new ServiceAccess(true, true, 1, $invalidValue, $invalidValue);

        $authConfig = $this->createConfiguredMock(AuthConfig::class, [
            'getSecretKey' => 'test-secret-key',
            'getHashAlgo' => HashAlgo::SHA256,
        ]);

        $factory = new RateLimitFactory($identity, $access, $authConfig);

        $actual = $factory->createFromRequest($this->createMock(Request::class));

        $this->assertSame($this->serviceProtocol->getRateLimitWindow(), $actual->windowSeconds);
        $this->assertSame($this->serviceProtocol->getRateLimitMaxQuota(), $actual->maxUnits);
    }

    /**
     * @return array<string, int[]>
     */
    public static function invalidRateLimitValuesProvider(): array
    {
        return [
            'Zero' => [0],
            'Negative One' => [-1],
            'Large Negative' => [-999],
        ];
    }

    private function createRateLimit(
        ?string $fingerprint = null,
        ?string $action = null,
        ?int $windowSeconds = null,
        ?int $maxUnits = null,
    ): RateLimit {
        return new RateLimit(
            fingerprint: $fingerprint ?? 'df68935b02c8a59c2b05fe05051c8d52cb548934c0a82e1a184b2f3c62f711ea',
            action: $action ?? ' ',
            windowSeconds: $windowSeconds ?? $this->serviceProtocol->getRateLimitWindow(),
            maxUnits: $maxUnits ?? $this->serviceProtocol->getRateLimitMaxQuota(),
        );
    }
}
