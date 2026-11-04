<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceContract\Registry;

use ILIAS\ApiGateway\ServiceContract\Model\ServiceSpec;
use ILIAS\ApiGateway\ServiceContract\Registry\RepresentationService;
use ILIAS\ApiGateway\ServiceContract\Representations\Representation;
use ILIAS\ApiGateway\ServiceContract\Representations\RepresentationGenerator;
use ILIAS\ApiGateway\ServiceContract\Representations\RepresentationType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(RepresentationService::class)]
class RepresentationServiceTest extends TestCase
{
    private RepresentationService $service;
    private MockObject&RepresentationGenerator $generator;
    private RepresentationType $representationType;
    private ServiceSpec $serviceSpec;
    private string $representationTypeName;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RepresentationService([
            $this->generator = $this->createMock(RepresentationGenerator::class),
        ]);

        $this->representationType = RepresentationType::cases()[0];
        $this->serviceSpec = new ServiceSpec();
        $this->representationTypeName = $this->representationType->name;
    }

    public function testGeneratesRepresentation(): void
    {
        $expected = $this->createMock(Representation::class);

        $this->generator->expects(self::once())
            ->method('supports')
            ->with(self::identicalTo($this->representationType))
            ->willReturn(true);

        $this->generator
            ->expects(self::once())
            ->method('generate')
            ->with(self::identicalTo($this->serviceSpec))
            ->willReturn($expected);

        $actual = $this->service->generate($this->representationType, $this->serviceSpec);

        self::assertSame($expected, $actual);
    }

    public function testThrowsExceptionInCaseOfNotSupportedRepresentationType(): void
    {
        $this->generator->expects(self::once())->method('supports')->willReturn(false);
        $this->generator->expects(self::never())->method('generate');

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage("No generator for representation [{$this->representationTypeName}]");

        $service = new RepresentationService([$this->generator]);

        $service->generate($this->representationType, $this->serviceSpec);
    }

    public function testThrowsExceptionInCaseOfNonExistingGenerator(): void
    {
        $this->generator->expects(self::never())->method('supports');
        $this->generator->expects(self::never())->method('generate');

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage("No generator for representation [{$this->representationTypeName}]");

        $service = new RepresentationService([]);

        $service->generate($this->representationType, $this->serviceSpec);
    }
}
