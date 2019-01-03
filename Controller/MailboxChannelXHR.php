<?php

namespace Webkul\UVDesk\MailboxBundle\Controller;

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
}
