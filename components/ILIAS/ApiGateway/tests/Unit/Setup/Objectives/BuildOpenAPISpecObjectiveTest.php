<?php

declare(strict_types=1);

namespace Tests\Unit\Setup\Objectives;

use ILIAS\ApiGateway\Application\Factories\ServiceSpecFactory;
use ILIAS\ApiGateway\Routing\Route;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use ILIAS\ApiGateway\ServiceContract\Model\ServiceSpec;
use ILIAS\ApiGateway\ServiceContract\Registry\RepresentationService;
use ILIAS\ApiGateway\ServiceContract\Representations\Representation;
use ILIAS\ApiGateway\ServiceContract\Representations\RepresentationType;
use ILIAS\ApiGateway\Setup\Objectives\BuildOpenAPISpecObjective;
use ILIAS\ApiGateway\Setup\Artifact\OpenApiSpecArtifact;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(BuildOpenAPISpecObjective::class)]
final class BuildOpenAPISpecObjectiveTest extends TestCase
{
    private const string ARTIFACT_NAME = BuildOpenAPISpecObjective::ARTIFACT_NAME;
    private BuildOpenAPISpecObjective $objective;
    private MockObject&RoutesRegistry $routesRegistry;
    private MockObject&ServiceSpecFactory $serviceSpecFactory;
    private MockObject&RepresentationService $representationService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->objective = new BuildOpenAPISpecObjective(
            $this->routesRegistry = $this->createMock(RoutesRegistry::class),
            $this->serviceSpecFactory = $this->createMock(ServiceSpecFactory::class),
            $this->representationService = $this->createMock(RepresentationService::class),
        );
    }

    public function testHasAccessToArtifactName(): void
    {
        $this->assertSame(
            self::ARTIFACT_NAME,
            $this->objective->getArtifactName(),
        );
    }

    public function testUsesComponentsToBuildArtifact(): void
    {
        $routes = [
            'route1' => $route1 = $this->createMock(Route::class),
            'route2' => $route2 = $this->createMock(Route::class),
            'route3' => $route3 = $this->createMock(Route::class),
        ];

        $serviceContractFactoryArgs = [
            $this->identicalTo($route1),
            $this->identicalTo($route2),
            $this->identicalTo($route3),
        ];

        $serviceContract = new ServiceSpec();

        $representation = $this->createConfiguredMock(Representation::class, [
            'jsonSerialize' => $serialized = ['foo' => 'bar'],
        ]);

        $this->routesRegistry->expects($this->once())
            ->method('all')
            ->willReturn($routes);

        $this->serviceSpecFactory->expects($this->once())
            ->method('createFromRoutes')
            ->with(...$serviceContractFactoryArgs)
            ->willReturn($serviceContract);

        $this->representationService->expects($this->once())
            ->method('generate')
            ->with(
                $this->identicalTo(RepresentationType::OpenAPI),
                $this->identicalTo($serviceContract),
            )
            ->willReturn($representation);

        $expected = new OpenApiSpecArtifact($serialized);
        $actual = $this->objective->build();

        $this->assertEquals($expected, $actual);
    }

    public function testBuildThrowsExceptionIfRepresentationIsNotAnArray(): void
    {
        $representation = $this->createConfiguredMock(Representation::class, [
            'jsonSerialize' => 'not-an-array',
        ]);

        $this->routesRegistry->method('all')->willReturn([]);
        $this->serviceSpecFactory->method('createFromRoutes')->willReturn(new ServiceSpec());
        $this->representationService->method('generate')->willReturn($representation);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAPI representation must serialize to an array');

        $this->objective->build();
    }
}
