<?php

namespace Webkul\UVDesk\MailboxBundle\Package;

use Webkul\UVDesk\PackageManager\Extensions\HelpdeskExtension;
use Webkul\UVDesk\PackageManager\ExtensionOptions\HelpdeskExtension\Section as HelpdeskSection;

class UVDeskMailboxConfiguration extends HelpdeskExtension
{
    const WORKFLOW_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M25.783,21.527L10.245,6.019,6.016,10.248,21.524,25.756ZM37.512,6.019l6.119,6.119L6.016,49.783l4.229,4.229L47.89,16.4l6.119,6.119V6.019h-16.5ZM38.5,34.245l-4.229,4.229,9.389,9.389-6.149,6.149h16.5v-16.5L47.89,43.634Z" />
SVG;

    public function loadDashboardItems()
    {
        return [
            HelpdeskSection::SETTINGS => [
                [
                    'name' => 'Mailbox',
                    'route' => 'helpdesk_member_mailbox_settings',
                    'brick_svg' => self::WORKFLOW_BRICK_SVG,
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
