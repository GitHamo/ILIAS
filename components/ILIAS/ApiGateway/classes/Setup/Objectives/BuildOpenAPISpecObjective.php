<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\ApiGateway\Setup\Objectives;

use ILIAS\ApiGateway\Application\Factories\ServiceSpecFactory;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use ILIAS\ApiGateway\ServiceContract\Registry\RepresentationService;
use ILIAS\ApiGateway\ServiceContract\Representations\RepresentationType;
use ILIAS\ApiGateway\Setup\Artifact\OpenApiSpecArtifact;
use ILIAS\Setup\Artifact\BuildArtifactObjective as ilBuildArtifactObjective;
use ILIAS\Setup\Artifact as ilSetupArtifact;
use Override;

class BuildOpenAPISpecObjective extends ilBuildArtifactObjective
{
    public const string ARTIFACT_NAME = "openapi_spec";

    public function __construct(
        private readonly RoutesRegistry $routesRegistry,
        private readonly ServiceSpecFactory $serviceSpecFactory,
        private readonly RepresentationService $representationService,
    ) {
    }

    #[Override]
    public function getArtifactName(): string
    {
        return self::ARTIFACT_NAME;
    }

    #[Override]
    public function build(): ilSetupArtifact
    {
        $routes = $this->routesRegistry->all();
        $spec = $this->serviceSpecFactory->createFromRoutes(...array_values($routes));
        $representation = $this->representationService->generate(RepresentationType::OpenAPI, $spec);
        $representationData = $representation->jsonSerialize();

        if (!\is_array($representationData)) {
            throw new \RuntimeException('OpenAPI representation must serialize to an array');
        }

        return new OpenApiSpecArtifact($representationData);
    }
}
