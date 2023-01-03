<?php

namespace Webkul\UVDesk\MailboxBundle\Workflow\Events\Email;

use Webkul\UVDesk\AutomationBundle\Workflow\Events\EmailActivity;

class EmailRecieved extends EmailActivity
{
    public static function getId()
    {
        return 'uvdesk.email.received';
    }

    public static function getDescription()
    {
        return "Email Received";
    }
}
