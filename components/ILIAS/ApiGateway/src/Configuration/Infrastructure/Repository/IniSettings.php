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

namespace ILIAS\ApiGateway\Configuration\Infrastructure\Repository;

use ILIAS\ApiGateway\GlobalDICAccessTrait;
use ilDBInterface;
use ilIniFile;
use ilSetting;

/**
 * @codeCoverageIgnore To be tested when settings are loaded by DI not global
 */
class IniSettings
{
    use GlobalDICAccessTrait;

    private ?ilIniFile $ilias = null;
    private ?ilIniFile $client = null;

    public function getHttpPath(): string
    {
        return (string) $this->ilias()?->readVariable('server', 'http_path');
    }

    public function getClientId(): string
    {
        return (string) $this->client()?->readVariable('client', 'name');
    }

    public function isDebugEnabled(): bool
    {
        return (bool) $this->client()?->readVariable('system', 'DEVMODE');
    }

    public function isLoggingEnabled(): bool
    {
        return (bool) $this->ilias()?->readVariable('log', 'enabled');
    }

    private function ilias(): ?ilIniFile
    {
        if (!$this->ilias instanceof ilIniFile) {
            $this->ilias = $this->dic()?->iliasIni();
        }

        return $this->ilias;
    }

    private function client(): ?ilIniFile
    {
        if (!$this->client instanceof ilIniFile) {
            $this->client = $this->dic()?->clientIni();
        }

        return $this->client;
    }
}
