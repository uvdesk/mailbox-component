<?php

namespace Webkul\UVDesk\MailboxBundle\Controller;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MailboxChannelXHR extends Controller
{
    public function processMailXHR(Request $request)
    {
        // Return HTTP_OK Response
        $response = new Response(Response::HTTP_OK);
        $response->send();

        if ("POST" == $request->getMethod() && null != $request->get('email')) {
            $this->get('uvdesk.mailbox')->processMail($request->get('email'));
        }
        
        exit(0);
    }

    public function createMailbox(Request $request)
    {
        // Default values
        $updateFile = false;
        $message = "Something went wrong";
        $filePath = dirname(__FILE__, 5) . '/config/packages/uvdesk_mailbox.yaml';
        
        // New mail box details fetch from request
        $data = json_decode($request->getContent(), true);
        $isExistingMailbox = $this->checkExistingMailbox($data['mailbox_id'], $data["imap['email']"]);

        if (!$isExistingMailbox) {
            $newMailbox[$data['mailbox_id']] = [
                'name' => $data['sender-name'],
                'enabled' => true,
                'smtp_server' => [
                    'mailer_id' => $data['swiftmailer-id'],
                ],
                'imap_server' => [
                    'username' => $data["imap['email']"],
                    'host' => $data["imap['host']"],
                    'password' => $data["imap['password']"]
                ],
            ];

            $file_content_array = Yaml::parse(file_get_contents($filePath), 6);
            if (isset($file_content_array['uvdesk_mailbox']['mailboxes']) && $file_content_array['uvdesk_mailbox']['mailboxes']) {
                $existingMailboxesCount = sizeof($file_content_array['uvdesk_mailbox']['mailboxes']);
                $file_content_array['uvdesk_mailbox']['mailboxes'] = array_merge($file_content_array['uvdesk_mailbox']['mailboxes'], $newMailbox);
            } else {
                $file_content_array['uvdesk_mailbox']['mailboxes'] = $newMailbox;
            }

            $updateFile = file_put_contents($filePath, Yaml::dump($file_content_array, 6));
        } else {
            $updateFile = false;
            $message = "Mailbox already exist.";
        }
        
        if ($updateFile) {
            $newMailbox[$data['mailbox_id']]['mailbox_id'] = $data['mailbox_id'];
            $result = [
                'mailbox' => $newMailbox[$data['mailbox_id']],
                'alertClass' => "success",
                'alertMessage' => "Success ! Mailbox saved successfully.",
            ];
        } else {
            $result = [
                'alertClass' => "error",
                'alertMessage' => "Error ! " . $message,
            ];
        }

        return new Response(json_encode($result), 200, ['Content-Type: application/json']);
    }

    public function updateMailbox(Request $request)
    {
        // Default values
        $updateFile = false;
        $message = "Something went wrong";
        $filePath = dirname(__FILE__, 5) . '/config/packages/uvdesk_mailbox.yaml';

        $mailbox_id = $request->query->get('id');
        $data = json_decode($request->getContent(), true);

        $isExistingMailbox = $this->checkExistingMailbox($mailbox_id, $data["imap['email']"]);
        $availableMailboxWithNewEmail = $this->checkAvailabilityForEmail($mailbox_id, $data["imap['email']"]);

        if ($isExistingMailbox && $availableMailboxWithNewEmail) {
            $this->removeExistingMailbox($mailbox_id);

            $editedDetails[$data['mailbox_id']] = [
                'name' => $data['sender-name'],
                'enabled' => $data['swiftmailer-id'] ? true : false,
                'smtp_server' => [
                    'mailer_id' => $data['swiftmailer-id'],
                ],
                'imap_server' => [
                    'username' => $data["imap['email']"],
                    'host' => $data["imap['host']"],
                    'password' => $data["imap['password']"]
                ],
            ];

            $file_content_array = Yaml::parse(file_get_contents($filePath), 6);
            if (isset($file_content_array['uvdesk_mailbox']['mailboxes']) && $file_content_array['uvdesk_mailbox']['mailboxes']) {
                $existingMailboxesCount = sizeof($file_content_array['uvdesk_mailbox']['mailboxes']);
                $file_content_array['uvdesk_mailbox']['mailboxes'] = array_merge($file_content_array['uvdesk_mailbox']['mailboxes'], $editedDetails);
            } else {
                $file_content_array['uvdesk_mailbox']['mailboxes'] = $editedDetails;
            }

            $updateFile = file_put_contents($filePath, Yaml::dump($file_content_array, 6));
        } else if (!$availableMailboxWithNewEmail) {
            $updateFile = false;
            $message = "This email id is already registered with mailbox.";
        } else {
            $updateFile = false;
            $message = "Mailbox do not exist.";
        }

        if ($updateFile) {
            $editedDetails[$data['mailbox_id']]['mailbox_id'] = $data['mailbox_id'];
            $result = [
                'mailbox' => $editedDetails[$data['mailbox_id']],
                'alertClass' => "success",
                'alertMessage' => "Success ! Mailbox edited successfully.",
            ];
        } else {
            $result = [
                'alertClass' => "error",
                'alertMessage' => "Error ! " . $message,
            ];
        }

        return new Response(json_encode($result), 200, ['Content-Type: application/json']);
    }

    public function removeMailbox(Request $request)
    {
        $mailboxId= $request->query->get('id');

        $isExistingMailbox = $this->checkExistingMailbox($mailboxId);
        if ($isExistingMailbox) {
            $alertClass = "success";
            $alertMessage = "Success ! Mailbox deleted successfully.";
            $isRemoved = $this->removeExistingMailbox($mailboxId);
        } else {
            $alertClass = "error";
            $alertMessage = "Error ! Mailbox do not exist.";
        }

        $result = [
            'alertClass' => $alertClass,
            'alertMessage' => $alertMessage,
        ];

        return new Response(json_encode($result), 200, ['Content-Type: application/json']);
    }

    private function checkExistingMailbox($unique_id = null, $mail_id = null)
    {
        $isExist = false;
        $file_content_array = Yaml::parse(file_get_contents(dirname(__FILE__, 5) . '/config/packages/uvdesk_mailbox.yaml'), 6);

        $existingMailboxes = $file_content_array['uvdesk_mailbox']['mailboxes'];
        if ($existingMailboxes) {
            foreach ($existingMailboxes as $index => $mailboxDetails) {
                if (($index === $unique_id) || ($mail_id && $mailboxDetails['imap_server']['username'] == $mail_id)) {
                    $isExist = true;
                }
            }
        }

        return $isExist;
    }

    private function checkAvailabilityForEmail($unique_id, $mail_id)
    {
        $isAvailable = true;
        $file_content_array = Yaml::parse(file_get_contents(dirname(__FILE__, 5) . '/config/packages/uvdesk_mailbox.yaml'), 6);

        $existingMailboxes = $file_content_array['uvdesk_mailbox']['mailboxes'];
        if ($existingMailboxes) {
            foreach ($existingMailboxes as $index => $mailboxDetails) {
                if ($mailboxDetails['imap_server']['username'] == $mail_id && $unique_id !== (string) $index) {
                    $isAvailable = false;
                }
            }
        }

        return $isAvailable;
    }

    public function arrayToString($array)
    {
        return implode(PHP_EOL, array_map(function ($v, $k) {
                if (is_array($v)) {
                    return $k.'[]='.implode('&'.$k.'[]=', $v);
                } else {
                    return $k.': '.$v;
                }
            }, 
            $array, 
            array_keys($array)
        ));
    }

    private function removeExistingMailbox($mailboxId)
    {
        $filePath = dirname(__FILE__, 5) . '/config/packages/uvdesk_mailbox.yaml';
        $file_content_array = Yaml::parse(file_get_contents($filePath), 6);

        $mailboxes = $file_content_array['uvdesk_mailbox']['mailboxes'];
        if (isset($mailboxes[$mailboxId])) {
            unset($mailboxes[$mailboxId]);

            if (empty($mailboxes))
                $mailboxes = null;
            $file_content_array['uvdesk_mailbox']['mailboxes'] = $mailboxes;
        }

        // Final write the content with new mailbox details in file
        $updateFile = file_put_contents($filePath, Yaml::dump($file_content_array, 6));

        return $updateFile ? true : false;
    }
}
