<?php

namespace Webkul\UVDesk\MailboxBundle\Controller;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MailboxChannel extends Controller
{
    public function loadSettings(Request $request)
    {
        $mailboxCollection = $this->container->get('uvdesk.mailbox')->getRegisteredMailboxesWithId();

        return $this->render('@UVDeskMailbox//settings.html.twig', [
            'swiftmailers' => $this->container->get('swiftmailer.service')->getSwiftmailerIds(),
            'mailboxes' => json_encode($mailboxCollection)
        ]);
    }
}
