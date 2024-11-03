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

namespace ILIAS\File\Capabilities;

use ILIAS\Services\WOPI\Discovery\ActionRepository;
use ILIAS\File\Capabilities\Check\Download;
use ILIAS\File\Capabilities\Check\EditContent;
use ILIAS\File\Capabilities\Check\Manage;
use ILIAS\File\Capabilities\Check\Unzip;
use ILIAS\File\Capabilities\Check\ViewContent;
use ILIAS\File\Capabilities\Check\None;
use ILIAS\File\Capabilities\Check\Info;
use ILIAS\File\Capabilities\Check\Edit;
use ILIAS\File\Capabilities\Check\Check;
use ILIAS\File\Capabilities\Check\CheckHelpers;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class CapabilityBuilder
{
    protected array $cache = [];
    /**
     * @var Check[]
     */
    private array $checks = [];

    public function __construct(
        private \ilObjFileInfoRepository $file_info_repository,
        private \ilAccessHandler $access,
        private \ilCtrlInterface $ctrl,
        private ActionRepository $action_repository
    ) {
        $this->checks = [
            new Download(),
            new Edit(),
            new EditContent(),
            new Info(),
            new Manage(),
            new None(),
            new Unzip(),
            new ViewContent(),
        ];
    }

    public function get(int $ref_id, bool $do_checks = true): CapabilityCollection
    {
        if (isset($this->cache[$ref_id])) {
            return $this->cache[$ref_id];
        }

        /**
         * This is the order of priorities when
         * using @see CapabilityCollection::getBest()
         * which will return the first unlocked Capability
         */
        $capabilities = [
            new Capability(Capabilities::VIEW_EXTERNAL, Permissions::VIEW_CONTENT),
            new Capability(Capabilities::DOWNLOAD, Permissions::READ),
            new Capability(Capabilities::EDIT_EXTERNAL, Permissions::EDIT_FILE),
            new Capability(Capabilities::MANAGE_VERSIONS, Permissions::WRITE),
            new Capability(Capabilities::EDIT_SETTINGS, Permissions::WRITE),
            new Capability(Capabilities::INFO_PAGE, Permissions::VISIBLE),
            new Capability(Capabilities::NONE, Permissions::NONE),
            new Capability(Capabilities::UNZIP, Permissions::WRITE),
        ];


        if (\ilObject2::_lookupType($ref_id, true) !== 'file') {
            return new CapabilityCollection($capabilities);
        }

        $info = $this->file_info_repository->getByRefId($ref_id);
        $helpers = new CheckHelpers($this->access, $this->ctrl, $this->action_repository);
        $this->ctrl->setParameterByClass(\ilObjFileGUI::class, 'ref_id', $ref_id);

        foreach ($capabilities as $capability) {
            foreach ($this->checks as $check) {
                if ($check->canUnlock() === $capability->getCapability()) {
                    $capability = $check->maybeUnlock($capability, $helpers, $info, $ref_id);
                    $capability = $check->maybeBuildURI($capability, $helpers, $ref_id);
                }
            }
        }
        return $this->cache[$ref_id] = new CapabilityCollection($capabilities);
    }

}
