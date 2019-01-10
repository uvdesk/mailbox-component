<?php

namespace Webkul\UVDesk\MailboxBundle\Package;

use Webkul\UVDesk\PackageManager\Extensions\HelpdeskExtension;
use Webkul\UVDesk\PackageManager\ExtensionOptions\HelpdeskExtension\Section as HelpdeskSection;

class UVDeskMailboxConfiguration extends HelpdeskExtension
{
    const MAILBOX_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M30,33L6,18V12L30,27,54,12v6ZM5.9,5.992A5.589,5.589,0,0,0,1.745,7.817,5.882,5.882,0,0,0-.016,12.027v35.93a5.875,5.875,0,0,0,1.761,4.211A5.581,5.581,0,0,0,5.9,53.992H54.069a5.588,5.588,0,0,0,4.155-1.825A5.8,5.8,0,0,0,60,48V12a5.847,5.847,0,0,0-1.776-4.183,5.6,5.6,0,0,0-4.155-1.825H5.9Z" />
SVG;

    public function loadDashboardItems()
    {
        return [
            HelpdeskSection::SETTINGS => [
                [
                    'name' => 'Mailbox',
                    'route' => 'helpdesk_member_mailbox_settings',
                    'brick_svg' => self::MAILBOX_BRICK_SVG,
                    'permission' => 'ROLE_AGENT_MANAGE_WORKFLOW_AUTOMATIC',
                ],
            ],
        ];
    }

    public function loadNavigationItems()
    {
        return [];
    }
}
