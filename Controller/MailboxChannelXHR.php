<?php

namespace Webkul\UVDesk\MailboxBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\MailboxBundle\Utils\MailboxConfiguration;
use Webkul\UVDesk\MailboxBundle\Services\MailboxService;
use Symfony\Component\Translation\TranslatorInterface;

class MailboxChannelXHR extends AbstractController
{
    private $mailboxService;
    private $translator;

    public function __construct(MailboxService $mailboxService, TranslatorInterface $translator)
    {
        $this->mailboxService = $mailboxService;
        $this->translator = $translator;
    }

    public function processMailXHR(Request $request)
    {
        // Return HTTP_OK Response
        $response = new Response(Response::HTTP_OK);
        $response->send();

        if ("POST" == $request->getMethod() && null != $request->get('email')) {
            $this->mailboxService->processMail($request->get('email'));
        }
        
        exit(0);
    }
    
    public function loadMailboxesXHR(Request $request)
    {
        $collection = array_map(function ($mailbox) {
            return [
                'id' => $mailbox->getId(),
                'name' => $mailbox->getName(),
                'isEnabled' => $mailbox->getIsEnabled(),
                'isDeleted' => $mailbox->getIsDeleted(),
            ];
        }, $this->mailboxService->parseMailboxConfigurations()->getMailboxes());

        return new JsonResponse($collection ?? []);
    }

    public function removeMailboxConfiguration($id, Request $request)
    {
        $mailboxService = $this->mailboxService;
        $existingMailboxConfiguration = $mailboxService->parseMailboxConfigurations();

        foreach ($existingMailboxConfiguration->getMailboxes() as $configuration) {
            if ($configuration->getId() == $id) {
                $mailbox = $configuration;

                break;
            }
        }

        if (empty($mailbox)) {
            return new JsonResponse([
                'alertClass' => 'danger',
                'alertMessage' => "No mailbox found with id '$id'.",
            ], 404);
        }

        $mailboxConfiguration = new MailboxConfiguration();

        foreach ($existingMailboxConfiguration->getMailboxes() as $configuration) {
            if ($configuration->getId() == $id) {
                continue;
            }

            $mailboxConfiguration->addMailbox($configuration);
        }

        file_put_contents($mailboxService->getPathToConfigurationFile(), (string) $mailboxConfiguration);

        return new JsonResponse([
            'alertClass' => 'success',
            'alertMessage' => $this->translator->trans('Mailbox configuration removed successfully.'),
        ]);
    }
}
