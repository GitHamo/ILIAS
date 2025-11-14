<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use ILIAS\ApiGateway\Activity\ActivityNamespaceFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ActivityNamespaceFactoryTest extends TestCase
{
    private ActivityNamespaceFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new ActivityNamespaceFactory();
    }

    #[DataProvider('classNameToPathProvider')]
    public function testGetsNamespacePath(
        string $namespace,
        string $expected,
    ): void {
        $actual = $this->factory->create($namespace);

        self::assertSame(
            $expected,
            $actual->getPath()
        );
    }

    #[DataProvider('invalidClassNamesProvider')]
    public function testThrowsExceptionForInvalidClassName(
        string $className,
        string $expected,
    ): void {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            "{$expected} is not a proper name for a dependency."
        );

        $this->factory->create($className);
    }

    /**
     * @return array<string, string[]>
     */
    public static function classNameToPathProvider(): array
    {
        return [
            'standard activity name' => [
                'Foo\\Bar\\ActivityName',
                '/foo/bar/ActivityName'
            ],
            'activity name ending with Query' => [
                'Foo\\Bar\\MyQuery',
                '/foo/bar/My'
            ],
            'activity name with Query in middle' => [
                'Foo\\Bar\\QueryActivity',
                '/foo/bar/Activity'
            ],
            'activity name starting with Query' => [
                'Foo\\Bar\\QueryFoo',
                '/foo/bar/Foo'
            ],
            'activity name is just Query' => [
                'Foo\\Bar\\Query',
                '/foo/bar/'
            ],
            'activity name with mixed case' => [
                'Foo\\Bar\\activityName',
                '/foo/bar/ActivityName'
            ],
            'vendor and component with mixed case' => [
                'iLiAs\\aPiGaTeWaY\\ActivityName',
                '/ilias/apigateway/ActivityName'
            ],
            'vendor and component with numbers' => [
                'Vendor1\\Component2\\ActivityName',
                '/vendor1/component2/ActivityName'
            ],
            'class name with more than three parts' => [
                'Foo\\Bar\\Baz\\FooBarBaz\\SubComponent\\ActivityName',
                '/foo/bar/ActivityName'
            ],
        ];
    }

    /**
     * @return array<string, string[]>
     */
    public static function invalidClassNamesProvider(): array
    {
        return [
            'two parts' => ["Foo\\Bar", 'Foo\Bar'],
            'one part' => ["Foo", 'Foo'],
        ];
    }
}
