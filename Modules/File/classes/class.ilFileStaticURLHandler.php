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

use ILIAS\StaticURL\Handler\Handler;
use ILIAS\StaticURL\Request\Request;
use ILIAS\StaticURL\Handler\ilCtrlInterface;
use ILIAS\StaticURL\Context;
use ILIAS\StaticURL\Response\Response;
use ILIAS\StaticURL\Response\Factory;
use ILIAS\StaticURL\Handler\BaseHandler;
use ILIAS\File\Capabilities\CapabilityBuilder;
use ILIAS\Services\WOPI\Discovery\ActionDBRepository;
use ILIAS\File\Capabilities\Capabilities;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class ilFileStaticURLHandler extends BaseHandler implements Handler
{
    public const DOWNLOAD = 'download';
    public const VERSIONS = 'versions';
    public const EDIT = 'edit';
    public const VIEW = 'view';
    private CapabilityBuilder $capabilities;

    public function __construct()
    {
        global $DIC;
        parent::__construct();
        $this->capabilities = new CapabilityBuilder(
            new ilObjFileInfoRepository(),
            $DIC->access(),
            $DIC->ctrl(),
            new ActionDBRepository($DIC->database())
        );
    }

    public function getNamespace(): string
    {
        return 'file';
    }

    public function handle(Request $request, Context $context, Factory $response_factory): Response
    {
        $ref_id = $request->getReferenceId()?->toInt() ?? 0;
        $additional_params = $request->getAdditionalParameters()[0] ?? null;
        $context->ctrl()->setParameterByClass(ilObjFileGUI::class, 'ref_id', $ref_id);

        if ($additional_params === "_wsp") {
            ilObjectGUI::_gotoSharedWorkspaceNode((int) $ref_id);
        }

        $capabilities = $this->capabilities->get($ref_id);

        $capability = match ($additional_params) {
            self::DOWNLOAD => $capabilities->get(Capabilities::DOWNLOAD),
            self::VERSIONS => $capabilities->get(Capabilities::MANAGE_VERSIONS),
            self::EDIT => $capabilities->get(Capabilities::EDIT_EXTERNAL),
            self::VIEW => $capabilities->get(Capabilities::VIEW_EXTERNAL),
            default => $capabilities->get(Capabilities::INFO_PAGE),
        };

        if (!$capability->isUnlocked() || $capability->getUri() === null) {
            return $response_factory->cannot();
        }

        $uri = $capability->getUri();

        return $response_factory->can($uri->getPath() . '?' . $uri->getQuery());
    }

}
