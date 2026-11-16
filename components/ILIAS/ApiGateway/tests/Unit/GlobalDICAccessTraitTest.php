<?php

declare(strict_types=1);

namespace Tests\Unit;

use ilDBInterface;
use ILIAS\ApiGateway\GlobalDICAccessTrait;
use ILIAS\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(GlobalDICAccessTrait::class)]
class GlobalDICAccessTraitTest extends TestCase
{
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
}
