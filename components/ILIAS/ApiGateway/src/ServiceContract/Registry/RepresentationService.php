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

namespace ILIAS\ApiGateway\ServiceContract\Registry;

use ILIAS\ApiGateway\ServiceContract\Model\ServiceSpec;
use ILIAS\ApiGateway\ServiceContract\Representations\Representation;
use ILIAS\ApiGateway\ServiceContract\Representations\RepresentationGenerator;
use ILIAS\ApiGateway\ServiceContract\Representations\RepresentationType;
use RuntimeException;

readonly class RepresentationService
{
    /** @param RepresentationGenerator[] $generators */
    public function __construct(private array $generators)
    {
    }

    public function generate(RepresentationType $type, ServiceSpec $spec): Representation
    {
        foreach ($this->generators as $generator) {
            if ($generator->supports($type)) {
                return $generator->generate($spec);
            }
        }

        throw new RuntimeException("No generator for representation [$type->name]");
    }
}
