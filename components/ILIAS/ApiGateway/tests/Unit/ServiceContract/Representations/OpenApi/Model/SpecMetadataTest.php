<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceContract\Representations\OpenApi\Model;

use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\Model\SpecMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpecMetadata::class)]
final class SpecMetadataTest extends TestCase
{
    public function testConstructor(): void
    {
        $config = new SpecMetadata(
            baseUrl: 'https://api.example.com',
            version: '1.0',
            title: 'Example API',
            description: 'This is an example API',
            apiVersion: 'v1'
        );

        $this->assertSame('https://api.example.com', $config->baseUrl);
        $this->assertSame('1.0', $config->version);
        $this->assertSame('Example API', $config->title);
        $this->assertSame('This is an example API', $config->description);
        $this->assertSame('v1', $config->apiVersion);
    }
}
