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
        $mailboxCollection = $this->get('uvdesk.mailbox')->getRegisteredMailboxesById();
        $swiftmailerConfigurations = array_map(function ($configuartion) {
            return [
                'id' => $configuartion->getId(),
            ];
        }, $this->get('swiftmailer.service')->parseSwiftMailerConfigurations());

        return $this->render('@UVDeskMailbox//settings.html.twig', [
            'swiftmailers' => $swiftmailerConfigurations,
            'mailboxes' => json_encode($mailboxCollection)
        ]);
    }
}
