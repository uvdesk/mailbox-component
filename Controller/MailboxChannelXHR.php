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
}
