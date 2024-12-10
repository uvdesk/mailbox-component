<?php

namespace Webkul\UVDesk\MailboxBundle\Workflow\Actions\Email;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\AutomationBundle\Workflow\Action as WorkflowAction;
use Webkul\UVDesk\AutomationBundle\Workflow\Event;
use Webkul\UVDesk\AutomationBundle\Workflow\Events\EmailActivity;
use Webkul\UVDesk\AutomationBundle\Workflow\FunctionalGroup;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Attachment;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\EmailTemplates;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;

class FromEmail extends WorkflowAction
{
    public static function getId()
    {
        return 'uvdesk.email.use_address';
    }

    public static function getDescription()
    {
        return "From Email";
    }

    public static function getFunctionalGroup()
    {
        return FunctionalGroup::EMAIL;
    }

    public static function getOptions(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $emailTemplateCollection = array_map(function ($emailTemplate) {
            return [
                'id'   => $emailTemplate->getId(),
                'name' => $emailTemplate->getName(),
            ];
        }, $entityManager->getRepository(EmailTemplates::class)->findAll());

        return [
            [
                'id'   => 'use_same_address',
                'name' => "Use original email address",
            ], 
            [
                'id'   => 'use_reply_to_address',
                'name' => "Use reply-to email address as customer email address",
            ]
        ];
    }

    public static function applyAction(ContainerInterface $container, Event $event, $value = null)
    {
        if (! $event instanceof EmailActivity) {
            return;
        }

        $emailHeaders = $event->getEmailHeaders();
        $resolvedEmailHeaders = $event->getResolvedEmailHeaders();

        switch ($value) {
            case 'use_reply_to_address':
                if (
                    ! empty($resolvedEmailHeaders['from']) 
                    && !empty($resolvedEmailHeaders['reply-to'])
                ) {
                    $emailHeaders['from'] = $emailHeaders['reply-to'];
                    $resolvedEmailHeaders['from'] = $resolvedEmailHeaders['reply-to'];
                }

                break;
            default:
                break;
        }

        $event->setEmailHeaders($emailHeaders);
        $event->setResolvedEmailHeaders($resolvedEmailHeaders);
    }
}
