<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceContract\Model;

use ILIAS\ApiGateway\ServiceContract\Model\Schema;
use ILIAS\ApiGateway\ServiceContract\Model\SchemaType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Schema::class)]
class SchemaTest extends TestCase
{
    private Schema $schema;
    private SchemaType $type;
    private string $title;
    private string $description;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->title = 'foo';
        $this->description = 'bar';
        $this->type = SchemaType::cases()[0];

        $this->schema = new readonly class (
            $this->type,
            $this->title,
            $this->description,
        ) extends Schema {
            public function __construct(
                private SchemaType $type,
                string $title,
                string $description,
            ) {
                parent::__construct($title, $description);
            }

            public function getType(): SchemaType
            {
                return $this->type;
            }
        };
    }

    public function testHasAccessorToTitle(): void
    {
        self::assertSame(
            $this->title,
            $this->schema->getTitle(),
        );
    }

    public function testHasAccessorToDescription(): void
    {
        self::assertSame(
            $this->description,
            $this->schema->getDescription(),
        );
    }

    public function testCreatesInstanceOfSchemaWithMinimumArguments(): void
    {
        $actual = new readonly class ($this->type) extends Schema {
            public function __construct(
                private SchemaType $type,
            ) {
                parent::__construct();
            }

            public function getType(): SchemaType
            {
                return $this->type;
            }
        };

        self::assertNull(
            $actual->getTitle(),
        );

        self::assertNull(
            $actual->getDescription(),
        );
    }
}
