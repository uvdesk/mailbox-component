<?php

namespace Webkul\UVDesk\MailboxBundle\Utils;

use Webkul\UVDesk\MailboxBundle\Utils\Mailbox\Mailbox;
use Webkul\UVDesk\MailboxBundle\Utils\IMAP;

final class MailboxConfiguration
{
    const DEFAULT_TEMPLATE = __DIR__ . "/../Templates/Default.yaml";
    const CONFIGURATION_TEMPLATE = __DIR__ . "/../Templates/Mailbox/Mailbox.php";

    private $collection = [];
    private $defaultMailbox = null;

    public function __construct($defaultMailbox = null)
    {
        $this->defaultMailbox = $defaultMailbox;
    }

    public function addMailbox(Mailbox $mailbox)
    {
        $imapConfiguration = $mailbox->getImapConfiguration();

        if (
            ! empty($imapConfiguration) 
            && !$imapConfiguration instanceof IMAP\Transport\AppTransportConfigurationInterface 
            && !$imapConfiguration instanceof IMAP\Transport\SimpleTransportConfigurationInterface
        ) {
            if (preg_match('/"/', $imapConfiguration->getHost())) {
                $imapConfiguration->setHost(trim($imapConfiguration->getHost(), '"'));
            }
    
            if (preg_match("/'/", $imapConfiguration->getHost())) {
                $imapConfiguration->setHost(trim($imapConfiguration->getHost(), "'"));
            }

            $mailbox->setImapConfiguration($imapConfiguration);
        }

        $this->collection[$mailbox->getId()] = $mailbox;

        return $this;
    }

    public function removeMailbox(Mailbox $mailbox)
    {
        if ($mailbox->getId() != null && !empty($this->collection[$mailbox->getId()])) {
            unset($this->collection[$mailbox->getId()]);
        }

        return $this;
    }

    public function getMailboxes(): array
    {
        return $this->collection;
    }

    public function getDefaultMailbox(): ?Mailbox
    {
        if (
            ! empty($this->defaultMailbox) 
            && !empty($this->collection[$this->defaultMailbox])
        ) {
            return $this->collection[$this->defaultMailbox];
        }

        return null;
    }

    public function setDefaultMailbox(Mailbox $mailbox): self
    {
        if ($mailbox->getId() == null) {
            throw new \Exception("Cannot set the provided mailbox as default mailbox since no mailbox id is available.");
        } else if (empty($this->collection[$mailbox->getId()])) {
            throw new \Exception("Cannot set the provided mailbox as default mailbox since it's not part of the collection.");
        }

        $this->defaultMailbox = $mailbox->getId();

        return $this;
    }

    public function getMailboxById($mailboxId): ?Mailbox
    {
        if (! empty($this->collection[$mailboxId])) {
            return $this->collection[$mailboxId];
        }

        return null;
    }

    public function getMailboxByEmailAddress($mailboxEmail): ?Mailbox
    {
        foreach ($this->collection as $mailbox) {
            $smtpConfiguration = $mailbox->getSmtpConfiguration();

            if (! empty($smtpConfiguration) && $smtpConfiguration->getUsername() == $mailboxEmail) {
                return $mailbox;
            }
        }

        return null;
    }

    public function getIncomingMailboxByEmailAddress($mailboxEmail): ?Mailbox
    {
        foreach ($this->collection as $mailbox) {
            $imapConfiguration = $mailbox->getImapConfiguration();

            if (! empty($imapConfiguration) && $imapConfiguration->getUsername() == $mailboxEmail) {
                return $mailbox;
            }
        }

        return null;
    }

    public function getOutgoingMailboxByEmailAddress($mailboxEmail): ?Mailbox
    {
        return $this->getMailboxByEmailAddress($mailboxEmail);
    }

    public function __toString()
    {
        if (! empty($this->collection)) {
            $mailboxes = array_reduce($this->collection, function($mailboxes, $mailbox) {
                return $mailboxes . (string) $mailbox;
            }, '');

            return require self::CONFIGURATION_TEMPLATE;
        }

        return file_get_contents(self::DEFAULT_TEMPLATE);
    }
}