<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\Mailbox;

use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Webkul\UVDesk\MailboxBundle\Utils\Imap\ConfigurationInterface as ImapConfiguration;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer\BaseConfiguration as MailerConfiguration;
use Webkul\UVDesk\MailboxBundle\Utils\Imap\SimpleConfigurationInterface;

class Mailbox
{
    CONST TOKEN_RANGE = '12345';
    const MAILBOX_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/MailboxSettings.php";
    const DEFAULT_IMAP_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/Imap/Default.php";
    const SIMPLE_IMAP_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/Imap/SimpleImap.php";

    private $id = null;
    private $name = null;
    private $isEnabled = false;
    private $isDeleted = false;
    private $imapConfiguration = null;
    private $mailerConfiguration = null;

    public function __construct($id = null)
    {
        $this->id = $id;
    }

    private function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setIsEnabled(bool $isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function getIsEnabled() : bool
    {
        return $this->isEnabled;
    }

    public function getIsDeleted() : bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted)
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function setImapConfiguration(ImapConfiguration $imapConfiguration)
    {
        $this->imapConfiguration = $imapConfiguration;

        return $this;
    }

    public function getImapConfiguration() : ?ImapConfiguration
    {
        return $this->imapConfiguration;
    }

    public function setMailerConfiguration(MailerConfiguration $mailerConfiguration)
    {
        $this->mailerConfiguration = $mailerConfiguration;

        return $this;
    }

    public function getMailerConfiguration() : ?MailerConfiguration
    {
        return $this->mailerConfiguration;
    }

    public function __toString()
    {
        if (null == $this->getId()) {
            // Set random id
            $this->setId(sprintf("mailbox_%s", TokenGenerator::generateToken(4, self::TOKEN_RANGE)));
        }

        $imapConfiguration = $this->getImapConfiguration();
        $mailerConfiguration = $this->getMailerConfiguration();

        $imapTemplate = '';

        if ($imapConfiguration instanceof SimpleConfigurationInterface) {
            $imapTemplate = strtr(require self::SIMPLE_IMAP_TEMPLATE, [
                '[[ imap_username ]]' => $imapConfiguration->getUsername(),
            ]);
        } else {
            $imapTemplate = strtr(require self::DEFAULT_IMAP_TEMPLATE, [
                '[[ imap_host ]]' => $imapConfiguration->getHost(),
                '[[ imap_username ]]' => $imapConfiguration->getUsername(),
                '[[ imap_password ]]' => $imapConfiguration->getPassword(),
            ]);
        }

        return strtr(require self::MAILBOX_TEMPLATE, [
            '[[ id ]]' => $this->getId(),
            '[[ name ]]' => $this->getName(),
            '[[ status ]]' => $this->getIsEnabled() ? 'true' : 'false',
            '[[ delete_status ]]' => $this->getIsDeleted() ? 'true' : 'false',
            '[[ mailer_id ]]' => $mailerConfiguration ? $mailerConfiguration->getId() : '~',
            '[[ imap_settings ]]' => $imapTemplate,
        ]);
    }
}
