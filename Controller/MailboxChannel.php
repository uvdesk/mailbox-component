<?php

namespace Webkul\UVDesk\MailboxBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MailboxChannel extends Controller
{
    public function listMailbox(Request $request)
    {
        $mailboxService = $this->container->get('uvdesk.core.mailbox');
        $mailboxes = $mailboxService->getRegisteredMailboxes();

        return $this->render('@UVDeskMailbox//listMailboxes.html.html.twig', [
            'mailboxes' => json_encode($mailboxes)
        ]);
    }
}
