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

namespace ILIAS\ApiGateway\Setup;

use ilDatabaseUpdateStepsExecutedObjective;
use ilDatabaseUpdateStepsMetricsCollectedObjective;
use ILIAS\ApiGateway\Application\Factories\HttpConfigFactory;
use ILIAS\ApiGateway\Application\Factories\RoutesRegistryFactory;
use ILIAS\ApiGateway\Application\Factories\ServiceSpecFactory;
use ILIAS\ApiGateway\ServiceContract\Registry\RepresentationService;
use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi;
use ILIAS\ApiGateway\Setup\Objectives\ApiGatewayMetricsCollectedObjective;
use ILIAS\ApiGateway\Setup\Objectives\BuildOpenAPISpecObjective;
use ILIAS\ApiGateway\Setup\Steps\ApiGatewayDBUpdateSteps;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use ILIAS\Refinery\Factory;
use ILIAS\Setup;
use ILIAS\Setup\Objective;
use ilObjApiGateway;
use ilTreeAdminNodeAddedObjective;
use Override;

class ilApiGatewaySetupAgent extends Setup\Agent\NullAgent
{
    private const string SCHEMA_VERSION = '3.0.3';
    private const string SCHEMA_TITLE = 'ILIAS API Gateway';
    private const string SCHEMA_DESCRIPTION = 'Service Contract for the ILIAS API Gateway';
    private const string SCHEMA_API_VERSION = '1.0.0';

    public function __construct(
        Factory $refinery,
        private readonly HttpConfigFactory $configFactory,
        private readonly RoutesRegistryFactory $routesRegistryFactory,
    ) {
        parent::__construct($refinery);
    }

    #[Override]
    public function getUpdateObjective(?Setup\Config $config = null): Setup\Objective
    {
        return new Setup\ObjectiveCollection(
            'Database is updated for component/ILIAS/ApiGateway',
            true,
            new ilTreeAdminNodeAddedObjective(ilObjApiGateway::TYPE, 'ApiGateway'),
            new ilDatabaseUpdateStepsExecutedObjective(new ApiGatewayDBUpdateSteps()),
        );
    }

    #[Override]
    public function getStatusObjective(Setup\Metrics\Storage $storage): Setup\Objective
    {
        return new Setup\ObjectiveCollection(
            'Component ApiGateway',
            true,
            // component info & settings
            new ApiGatewayMetricsCollectedObjective(
                $storage,
            ),
            // database migrations
            new ilDatabaseUpdateStepsMetricsCollectedObjective(
                $storage,
                new ApiGatewayDBUpdateSteps(),
            ),
        );
    }

    #[Override]
    public function getBuildObjective(): Objective
    {
        $registry = $this->routesRegistryFactory->create();
        $factory = new ServiceSpecFactory();
        $specMetadata = new OpenApi\Model\SpecMetadata(
            $this->getBasePath(),
            self::SCHEMA_VERSION,
            self::SCHEMA_TITLE,
            self::SCHEMA_DESCRIPTION,
            self::SCHEMA_API_VERSION,
        );
        $serializer = new OpenApi\OpenApiSchemaSerializer();
        $generator = new OpenApi\OpenApiGenerator($specMetadata, $serializer);
        $service = new RepresentationService([
            $generator,
        ]);

        return new BuildOpenAPISpecObjective(
            $registry,
            $factory,
            $service,
        );
    }

    private function getBasePath(): string
    {
        return $this->configFactory->createWebConfig(ServiceProtocol::REST)->identity->basePath;
    }
}
