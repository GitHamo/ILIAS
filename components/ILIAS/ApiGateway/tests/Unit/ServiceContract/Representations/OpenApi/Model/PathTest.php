<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceContract\Representations\OpenApi\Model;

use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\Model\Operation;
use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\Model\Path;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Path::class)]
final class PathTest extends TestCase
{
    public function testHasAccessorToPath(): void
    {
        $model = new Path(
            $path = '/foo',
            [],
        );

        self::assertSame(
            $path,
            $model->getPath(),
        );
    }

    public function testSerializesOperations(): void
    {
        $model = new Path(
            'foo',
            [
                $this->createConfiguredMock(Operation::class, [
                    'getMethod' => 'BaR',
                    'jsonSerialize' => $serialized = [
                        'baz' => 'foobarbaz',
                    ],
                ]),
            ],
        );

        $expected = [
            'bar' => $serialized,
        ];

        $actual = $model->jsonSerialize();

        self::assertSame($expected, $actual);
    }
}
