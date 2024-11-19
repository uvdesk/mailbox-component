<?php

namespace Webkul\UVDesk\MailboxBundle\EventListener;

use Webkul\UVDesk\CoreFrameworkBundle\Mailer\Event\ConfigurationRemovedEvent;
use Webkul\UVDesk\CoreFrameworkBundle\Mailer\Event\ConfigurationUpdatedEvent;
use Webkul\UVDesk\MailboxBundle\Services\MailboxService;
use Webkul\UVDesk\MailboxBundle\Utils\Mailbox\Mailbox;

class Mailer
{
    private $mailboxService;

    public final function __construct(MailboxService $mailboxService)
    {
        $this->mailboxService = $mailboxService;
    }

    public function onMailerConfigurationUpdated(ConfigurationUpdatedEvent $event)
    {
        $isUpdateRequiredFlag = false;
        $updatedConfiguration = $event->getUpdatedMailerConfiguration();
        $existingConfiguration = $event->getExistingMailerConfiguration();
               
        if ($updatedConfiguration->getId() == $existingConfiguration->getId()) {
            // We only need to update if the mailer configuration's id has changed
            // or if it has been disabled.

            return;
        }

        $mailboxConfiguration = $this->mailboxService->parseMailboxConfigurations(true);

        foreach ($mailboxConfiguration->getMailboxes() as $existingMailbox) {
            if ($existingMailbox->getMailerConfiguration()->getId() == $existingConfiguration->getId()) {
                // Disable mailbox and update configuration
                $mailbox = new Mailbox($existingMailbox->getId());
                $mailbox->setName($existingMailbox->getName())
                    ->setIsEnabled($existingMailbox->getIsEnabled())
                    ->setImapConfiguration($existingMailbox->getImapConfiguration())
                    ->setMailerConfiguration($updatedConfiguration);
                
                $isUpdateRequiredFlag = true;
                $mailboxConfiguration->removeMailbox($existingMailbox);
                $mailboxConfiguration->addMailbox($mailbox);
            }
        }

        if (true === $isUpdateRequiredFlag) {
            file_put_contents($this->mailboxService->getPathToConfigurationFile(), (string) $mailboxConfiguration);
        }
        
        return;
    }

    public function onMailerConfigurationRemoved(ConfigurationRemovedEvent $event)
    {
        $isUpdateRequiredFlag = false;
        $configuration = $event->getMailerConfiguration(); 
        $mailboxConfiguration = $this->mailboxService->parseMailboxConfigurations();

        foreach ($mailboxConfiguration->getMailboxes() as $existingMailbox) {
                if (null != $existingMailbox->getMailerConfiguration() && $existingMailbox->getMailerConfiguration()->getId() == $configuration->getId()) {
                    // Disable mailbox and update configuration
                    $mailbox = new Mailbox($existingMailbox->getId());
                    $mailbox->setName($existingMailbox->getName())
                        ->setIsEnabled(false)
                        ->setImapConfiguration($existingMailbox->getImapConfiguration());

                    $isUpdateRequiredFlag = true;
                    $mailboxConfiguration->removeMailbox($existingMailbox);
                    $mailboxConfiguration->addMailbox($mailbox);
                }
        }

        if (true === $isUpdateRequiredFlag) {
            file_put_contents($this->mailboxService->getPathToConfigurationFile(), (string) $mailboxConfiguration);
        }

        return;
    }
}