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
        // $response = new Response(Response::HTTP_OK);
        // $response->send();

        if ("POST" == $request->getMethod() && null != $request->get('email')) {
            $this->get('uvdesk.mailbox')->processMail($request->get('email'));
        }
        
        exit(0);
    }

    public function listMailboxXHR(Request $request)
    {
        // if (!$this->get('user.service')->isAccessAuthorized('ROLE_AGENT_MANAGE_AGENT')) {
        //     return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        // }

        if (true === $request->isXmlHttpRequest()) {
            $mailboxCollection = [];

            foreach ($this->container->getParameter('uvdesk.mailboxes') as $mailbox_id) {
                $mailbox = $this->container->getParameter("uvdesk.mailboxes.$mailbox_id");
                
                $mailboxCollection[] = [
                    'id' => $mailbox_id,
                    'name' => $mailbox['name'],
                    'email' => $mailbox['email'],
                    'enabled' => $mailbox['enabled'],
                ];
            }

            // $userRepository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:User');
            // $agentCollection = $userRepository->getAllAgents($request->query, $this->container);

            return new Response(json_encode($mailboxCollection), 200, ['Content-Type' => 'application/json']);
        } 

        return new Response(json_encode([]), 404);
    }

    public function addMailbox(Request $request)
    {
        // Default values
        $updateFile = false;
        $message = "Something went wrong";
        $filePath = dirname(__FILE__, 5) . '/config/packages/uvdesk.yaml';
        
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

            $file_content_array = $this->getYamlContentAsArray($filePath);
            if (isset($file_content_array['uvdesk']['mailboxes']) && $file_content_array['uvdesk']['mailboxes']) {
                $existingMailboxesCount = sizeof($file_content_array['uvdesk']['mailboxes']);
                $file_content_array['uvdesk']['mailboxes'] = array_merge($file_content_array['uvdesk']['mailboxes'], $newMailbox);
            } else {
                $file_content_array['uvdesk']['mailboxes'] = $newMailbox;
            }

            $updateFile = $this->setYamlContent($filePath, $file_content_array);
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

    public function editExistingMailbox(Request $request)
    {
        // Default values
        $updateFile = false;
        $message = "Something went wrong";
        $filePath = dirname(__FILE__, 5) . '/config/packages/uvdesk.yaml';

        $mailbox_id = $request->query->get('id');
        $data = json_decode($request->getContent(), true);

        $isExistingMailbox = $this->checkExistingMailbox($mailbox_id, $data["imap['email']"]);
        if ($isExistingMailbox) {
            $this->removeExistingMailbox($mailbox_id);

            $editedDetails[$data['mailbox_id']] = [
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

            $file_content_array = $this->getYamlContentAsArray($filePath);
            if (isset($file_content_array['uvdesk']['mailboxes']) && $file_content_array['uvdesk']['mailboxes']) {
                $existingMailboxesCount = sizeof($file_content_array['uvdesk']['mailboxes']);
                $file_content_array['uvdesk']['mailboxes'] = array_merge($file_content_array['uvdesk']['mailboxes'], $editedDetails);
            } else {
                $file_content_array['uvdesk']['mailboxes'] = $editedDetails;
            }

            $updateFile = $this->setYamlContent($filePath, $file_content_array);
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

    public function removeExistingMailboxXHR(Request $request)
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

    private function setYamlContent($filePath, $arrayContent)
    {
        // Write the content with new mailbox details in file
        return file_put_contents($filePath, Yaml::dump($arrayContent, 6));
    }

    private function checkExistingMailbox($unique_id, $mail_id = null)
    {
        $isExist = false;
        $file_content_array = $this->getYamlContentAsArray(dirname(__FILE__, 5) . '/config/packages/uvdesk.yaml');

        $existingMailboxes = $file_content_array['uvdesk']['mailboxes'];
        if ($existingMailboxes) {
            foreach ($existingMailboxes as $index => $mailboxDetails) {
                if (($index === $unique_id) || ($mail_id && $mailboxDetails['imap_server']['username'] == $mail_id)) {
                    $isExist = true;
                }

            }
        }

        return $isExist;
    }

    private function getYamlContentAsArray($filePath)
    {
        // Fetch existing content in file
        $file_content = '';
        if ($fh = fopen($filePath, 'r')) {
            while (!feof($fh)) {
                $file_content = $file_content.fgets($fh);
            }
        }
        // Convert yaml file content into array and merge existing mailbox and new mailbox
        return Yaml::parse($file_content, 6);
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
        $filePath = dirname(__FILE__, 5) . '/config/packages/uvdesk.yaml';
        $file_content_array = $this->getYamlContentAsArray($filePath);

        $mailboxes = $file_content_array['uvdesk']['mailboxes'];
        if (isset($mailboxes[$mailboxId])) {
            unset($mailboxes[$mailboxId]);

            if (empty($mailboxes))
                $mailboxes = null;
            $file_content_array['uvdesk']['mailboxes'] = $mailboxes;
        }

        // Final write the content with new mailbox details in file
        $updateFile = $this->setYamlContent($filePath, $file_content_array);

        return $updateFile ? true : false;
    }
}
