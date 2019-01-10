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
        $mailboxCollection = $this->getRegisteredMailboxes();

        return $this->render('@UVDeskMailbox//settings.html.twig', [
            'swiftmailers' => $this->container->get('uvdesk.service')->getSwiftmailerIds(),
            'mailboxes' => json_encode($mailboxCollection)
        ]);
    }

    private function getRegisteredMailboxes()
    {
        // Fetch existing content in file
        $filePath = dirname(__FILE__, 5) . '/config/packages/uvdesk.yaml';
        $file_content = $this->getFileContent($filePath);

        // Convert yaml file content into array and merge existing mailbox and new mailbox
        $file_content_array = Yaml::parse($file_content, 6);

        if ($file_content_array['uvdesk']['mailboxes']) {
            foreach ($file_content_array['uvdesk']['mailboxes'] as $key => $value) {
                $value['mailbox_id'] = $key;
                $mailboxCollection[] = $value;
            }
        }

        return $mailboxCollection ?? [];
    }

    private function getFileContent($filePath)
    {
        $file_content = '';
        if ($fh = fopen($filePath, 'r')) {
            while (!feof($fh)) {
                $file_content = $file_content.fgets($fh);
            }
        }

        return $file_content;
    }
}
