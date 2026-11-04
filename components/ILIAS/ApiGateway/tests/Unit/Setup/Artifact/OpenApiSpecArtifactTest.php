<?php

declare(strict_types=1);

namespace Tests\Unit\Setup\Artifact;

use ILIAS\ApiGateway\Setup\Artifact\OpenApiSpecArtifact;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenApiSpecArtifact::class)]
final class OpenApiSpecArtifactTest extends TestCase
{
    public function testSerializeGeneratesDynamicPhpFunction(): void
    {
        $data = [
            'openapi' => '3.0.0',
            'servers' => [
                ['url' => '/base/path'],
            ],
            'paths' => [],
        ];

        $artifact = new OpenApiSpecArtifact($data);
        $serialized = $artifact->serialize();

        $this->assertStringContainsString('<?php return function (string $url): array {', $serialized);
        $this->assertStringContainsString("rtrim(\$url, '/') . '/' . 'base/path'", $serialized);

        $function = $this->getFunctionFromSerializedCode($serialized);

        $this->assertIsCallable($function);

        $result = $function('https://example.com');

        $this->assertSame('https://example.com/base/path', $result['servers'][0]['url']);
        $this->assertSame('3.0.0', $result['openapi']);
    }

    public function testSerializeHandlesMissingServerUrl(): void
    {
        $data = [
            'openapi' => '3.0.0',
            'paths' => [],
        ];

        $artifact = new OpenApiSpecArtifact($data);
        $serialized = $artifact->serialize();

        $function = $this->getFunctionFromSerializedCode($serialized);

        $result = $function('https://example.com');

        $this->assertSame('https://example.com', $result['servers'][0]['url']);
    }

    public function testSerializeHandlesEmptyServersArray(): void
    {
        $data = [
            'openapi' => '3.0.0',
            'servers' => [],
            'paths' => [],
        ];

        $artifact = new OpenApiSpecArtifact($data);
        $serialized = $artifact->serialize();

        $function = $this->getFunctionFromSerializedCode($serialized);

        $result = $function('https://example.com');

        $this->assertSame('https://example.com', $result['servers'][0]['url']);
    }

    private function getFunctionFromSerializedCode(string $serializedCode): callable
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_artifact_');
        file_put_contents($tempFile, $serializedCode);
        $function = include $tempFile;
        unlink($tempFile);

        return $function;
    }
}
