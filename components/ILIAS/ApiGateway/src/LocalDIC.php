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

namespace ILIAS\ApiGateway;

use ilDBInterface;
use ILIAS\DI\Container;

/**
 * This is a workaround to work with legacy dependncy.
 * When this is used, it is a temporarily adapter
 */
abstract class LocalDIC
{
    protected function dic(): ?Container
    {
        global $DIC;

        if (!isset($DIC) || !$DIC instanceof Container) {
            return null;
        }

        return $DIC;
    }

    protected function database(): ?ilDBInterface
    {
        return $this->dic()?->database();
    }
}
