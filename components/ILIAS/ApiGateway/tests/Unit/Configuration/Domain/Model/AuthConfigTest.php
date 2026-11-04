<?php

declare(strict_types=1);

namespace Tests\Unit\Configuration\Domain\Model;

use ILIAS\ApiGateway\Configuration\Domain\Enum\EncryptionAlgo;
use ILIAS\ApiGateway\Configuration\Domain\Enum\HashAlgo;
use ILIAS\ApiGateway\Configuration\Domain\Model\AuthConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthConfig::class)]
final class AuthConfigTest extends TestCase
{
    private AuthConfig $config;
    private string $issuer;
    private string $secretKey;
    private EncryptionAlgo $encryptionAlgo;
    private HashAlgo $hashAlgo;
    private int $accessTokenExpiry;
    private int $refreshTokenExpiry;

    #[\Override]
    protected function setUp(): void
    {
        $this->config = new AuthConfig(
            $this->issuer = 'http://localhost:1234',
            $this->secretKey = 'super-secret-key',
            $this->encryptionAlgo = EncryptionAlgo::HS256,
            $this->hashAlgo = HashAlgo::SHA256,
            $this->accessTokenExpiry = 1234567890,
            $this->refreshTokenExpiry = 9876543210,
        );
    }

    public function testHasAccessorToIssuer(): void
    {
        $this->assertSame(
            $this->issuer,
            $this->config->getIssuer(),
        );
    }

    public function testHasAccessorToSecretKey(): void
    {
        $this->assertSame(
            $this->secretKey,
            $this->config->getSecretKey(),
        );
    }

    public function testHasAccessorToEncryptionAlgo(): void
    {
        $this->assertSame(
            $this->encryptionAlgo,
            $this->config->getEncryptionAlgo(),
        );
    }

    public function testHasAccessorToHashAlgo(): void
    {
        $this->assertSame(
            $this->hashAlgo,
            $this->config->getHashAlgo(),
        );
    }

    public function testHasAccessorToAccessTokenExpiry(): void
    {
        $this->assertSame(
            $this->accessTokenExpiry,
            $this->config->getAccessTokenExpiry(),
        );
    }

    public function testHasAccessorToRefreshTokenExpiry(): void
    {
        $this->assertSame(
            $this->refreshTokenExpiry,
            $this->config->getRefreshTokenExpiry(),
        );
    }
}
