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

namespace ILIAS\File\Capabilities\Check;

use ILIAS\File\Capabilities\Capability;
use ILIAS\File\Capabilities\Capabilities;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class ViewContent extends BaseCheck implements Check
{
    public function canUnlock(): Capabilities
    {
        return Capabilities::VIEW_EXTERNAL;
    }

    public function maybeUnlock(
        Capability $capability,
        CheckHelpers $helpers,
        \ilObjFileInfo $info,
        int $ref_id,
    ): Capability {
        return $capability; // currrently not available
        if (!$this->hasPermission($helpers, $ref_id, $capability->getPermission())) {
            return $capability->withUnlocked(false);
        }

        return $capability->withUnlocked($this->hasWopiViewAction($helpers, $info->getSuffix()));
    }

    public function maybeBuildURI(Capability $capability, CheckHelpers $helpers, int $ref_id): Capability
    {
        if (!$capability->isUnlocked()) {
            return $capability;
        }
        return $capability->withURI(
            $helpers->fromTarget(
                $helpers->ctrl->getLinkTargetByClass(
                    [\ilRepositoryGUI::class, \ilObjFileGUI::class, \ilWOPIEmbeddedApplicationGUI::class],
                    \ilWOPIEmbeddedApplicationGUI::CMD_VIEW
                )
            )
        );
    }

}
