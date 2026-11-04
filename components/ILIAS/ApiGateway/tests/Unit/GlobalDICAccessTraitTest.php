<?php

declare(strict_types=1);

namespace Tests\Unit;

use ilDBInterface;
use ILIAS\ApiGateway\GlobalDICAccessTrait;
use ILIAS\DI\Container;
use ILIAS\DI\LoggingServices;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ilLogger;

#[CoversClass(GlobalDICAccessTrait::class)]
class GlobalDICAccessTraitTest extends TestCase
{
    private MockObject&Container $containerMock;

    #[\Override]
    protected function setUp(): void
    {
        global $DIC;

        $DIC = $this->containerMock = $this->createMock(Container::class);
    }

    public function testReturnsDatabase(): void
    {
        $expected = $this->createMock(ilDBInterface::class);

        $this->containerMock->expects(self::once())->method('database')->willReturn($expected);

        $actual = (new class () {
            use GlobalDICAccessTrait;

            public function getValue(): ilDBInterface
            {
                return $this->getDatabase();
            }
        })->getValue();

        $this->assertSame($expected, $actual);
    }

    public function testThrowsExceptionInCaseOfDatabaseIsNotSet(): void
    {
        global $DIC;

        $DIC = null;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No database connection');

        (new class () {
            use GlobalDICAccessTrait;

            public function __construct()
            {
                $this->getDatabase();
            }
        });
    }

    public function testDicReturnsNullIfGlobalDicIsMissing(): void
    {
        global $DIC;
        $DIC = null;

        $actual = (new class () {
            use GlobalDICAccessTrait;
            public function getDic(): ?Container
            {
                return $this->dic();
            }
        })->getDic();

        self::assertNull($actual);
    }

    public function testDicReturnsNullIfGlobalDicIsInvalidType(): void
    {
        global $DIC;
        $DIC = new \stdClass();

        $actual = (new class () {
            use GlobalDICAccessTrait;
            public function getDic(): ?Container
            {
                return $this->dic();
            }
        })->getDic();

        self::assertNull($actual);
    }

    public function testDatabaseReturnsNullIfDicIsMissing(): void
    {
        global $DIC;
        $DIC = null;

        $actual = (new class () {
            use GlobalDICAccessTrait;
            public function getDb(): ?ilDBInterface
            {
                return $this->database();
            }
        })->getDb();

        self::assertNull($actual);
    }

    public function testLoggerReturnsNullIfDicIsMissing(): void
    {
        global $DIC;
        $DIC = null;

        $actual = (new class () {
            use GlobalDICAccessTrait;
            public function getLog(): ?ilLogger
            {
                return $this->logger();
            }
        })->getLog();

        self::assertNull($actual);
    }

    public function testLoggerReturnsRootIfNameIsNull(): void
    {
        $loggerMock = $this->createMock(ilLogger::class);
        $loggingMock = $this->createMock(LoggingServices::class);
        $loggingMock->method('root')->willReturn($loggerMock);

        $this->containerMock->method('logger')->willReturn($loggingMock);

        $actual = (new class () {
            use GlobalDICAccessTrait;
            public function getLog(): ?ilLogger
            {
                return $this->logger();
            }
        })->getLog();

        self::assertSame($loggerMock, $actual);
    }

    public function testLoggerReturnsNamedLoggerIfValid(): void
    {
        $loggerMock = $this->createMock(ilLogger::class);
        $loggingMock = $this->createMock(LoggingServices::class);
        // Use a known method from LoggingServices if possible, or assume magic call works for components
        $loggingMock->method('__call')->with('someComponent', [])->willReturn($loggerMock);

        $this->containerMock->method('logger')->willReturn($loggingMock);

        $actual = (new class () {
            use GlobalDICAccessTrait;
            public function getLog(string $name): ?ilLogger
            {
                return $this->logger($name);
            }
        })->getLog('someComponent');

        self::assertSame($loggerMock, $actual);
    }
}
