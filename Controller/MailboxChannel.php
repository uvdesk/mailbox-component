<?php

namespace Webkul\UVDesk\MailboxBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MailboxChannel extends Controller
{
    public function listMailbox(Request $request)
    {
        // if (!$this->get('user.service')->isAccessAuthorized('ROLE_AGENT_MANAGE_MAILBOXES')){          
        //     return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        // }

        return $this->render('@UVDeskMailbox//listMailboxes.html.twig');
    }
}
