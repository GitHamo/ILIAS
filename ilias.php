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

/** @noRector */
require_once("libs/composer/vendor/autoload.php");
ilInitialisation::initILIAS();

/**
 * @var $DIC \ILIAS\DI\Container
 */
global $DIC;

try {
    $DIC->ctrl()->callBaseClass();
} catch (ilCtrlException $e) {
    $is_base_class_exception = (
        !str_contains($e->getMessage(), 'not given a baseclass') &&
        !str_contains($e->getMessage(), 'not a baseclass')
    );
    if ((defined('DEVMODE') && DEVMODE) || $is_base_class_exception) {
        if ($is_base_class_exception) {
            throw new RuntimeException('ilCtrl could not dispatch HTTP request due to missing/invalid base class ', 0, $e);
        }
        throw $e;
    }

    $DIC->logger()->root()->error($e->getMessage());
    $DIC->logger()->root()->error($e->getTraceAsString());
    $DIC->ctrl()->redirectToURL(ilUtil::_getHttpPath());
}

$DIC['ilBench']->save();
$DIC['http']?->close();
