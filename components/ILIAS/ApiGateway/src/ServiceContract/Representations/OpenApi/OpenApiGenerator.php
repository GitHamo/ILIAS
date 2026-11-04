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

namespace ILIAS\ApiGateway\ServiceContract\Representations\OpenApi;

use ILIAS\ApiGateway\ServiceContract\Model\Operation as ServiceContractOperation;
use ILIAS\ApiGateway\ServiceContract\Model\ServiceSpec;
use ILIAS\ApiGateway\ServiceContract\Representations\Representation;
use ILIAS\ApiGateway\ServiceContract\Representations\RepresentationGenerator;
use ILIAS\ApiGateway\ServiceContract\Representations\RepresentationType;
use ILIAS\ApiGateway\ServiceContract\Representations\SchemaSerializer;
use Override;

final readonly class OpenApiGenerator implements RepresentationGenerator
{
    public function __construct(
        private Model\SpecMetadata $specMetadata,
        private SchemaSerializer $serializer,
    ) {
    }

    #[Override]
    public function supports(RepresentationType $type): bool
    {
        return $type === RepresentationType::OpenAPI;
    }

    #[Override]
    public function generate(ServiceSpec $spec): Representation
    {
        $createPath = fn(ServiceContractOperation $operation): Model\Path =>

        new Model\Path(
            $operation->path,
            [
                Model\Operation::create(
                    $operation,
                    $this->serializer,
                ),
            ],
        );

        $paths = array_map($createPath, $spec->operations);

        return new OpenApiRepresentation(
            metadata: $this->specMetadata,
            paths: $paths,
        );
    }
}
