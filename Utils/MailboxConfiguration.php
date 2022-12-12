<?php

namespace Webkul\UVDesk\MailboxBundle\Utils;

use Webkul\UVDesk\MailboxBundle\Utils\Mailbox\Mailbox;
use Webkul\UVDesk\MailboxBundle\Utils\IMAP;

final class MailboxConfiguration
{
    const DEFAULT_TEMPLATE = __DIR__ . "/../Templates/config.yaml";
    const CONFIGURATION_TEMPLATE = __DIR__ . "/../Templates/Mailbox/Mailbox.php";

    private $collection = [];
    private $defaultMailbox = null;

    public function addMailbox(Mailbox $mailbox)
    {
        if (
            !$mailbox->getImapConfiguration() instanceof IMAP\Transport\AppTransportConfigurationInterface 
            && !$mailbox->getImapConfiguration() instanceof IMAP\Transport\SimpleTransportConfigurationInterface
        ) {
            if (preg_match('/"/', $mailbox->getImapConfiguration()->getHost())) {
                $mailbox->getImapConfiguration()->setHost(trim($mailbox->getImapConfiguration()->getHost(), '"')); 
            }
    
            if (preg_match("/'/", $mailbox->getImapConfiguration()->getHost())) {
                $mailbox->getImapConfiguration()->setHost(trim($mailbox->getImapConfiguration()->getHost(), "'")); 
            }

        }

        $this->collection[] = $mailbox;

        return $this;
    }

    public function removeMailbox(Mailbox $mailbox)
    {
        if ($mailbox->getId() != null) {
            foreach ($this->collection as $index => $configuration) {
                if ($configuration->getId() == null) {
                    continue;
                }
                
                if ($configuration->getId() == $mailbox->getId()) {
                    unset($this->collection[$index]);
                    break;
                }
            }
        }

        $this->collection = array_values($this->collection);

        return $this;
    }

    public function getMailboxes(): array
    {
        return $this->collection;
    }

    public function getMailbox($mailboxEmail): ?Mailbox
    {
        foreach ($this->collection as $mailbox) {
            $smtpConfiguration = $mailbox->getSmtpConfiguration();

            if (!empty($smtpConfiguration) && $smtpConfiguration->getUsername() == $mailboxEmail) {
                return $mailbox;
            }
        }

        return null;
    }

    public function getDefaultMailbox(): ?Mailbox
    {
        if (empty($this->defaultMailbox)) {
            foreach ($this->collection as $mailbox) {
                if ($mailbox->getIsDefault()) {
                    $this->defaultMailbox = $mailbox;
    
                    break;
                }
            }
        }

        return $this->defaultMailbox ?? null;
    }

    public function __toString()
    {
        if (!empty($this->collection)) {
            $stream = array_reduce($this->collection, function($stream, $mailbox) {
                return $stream . (string) $mailbox;
            }, '');
    
            return strtr(require self::CONFIGURATION_TEMPLATE, [
                '[[ MAILBOXES ]]' => $stream,
            ]);
        }

        return file_get_contents(self::DEFAULT_TEMPLATE);
    }
}
