<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\Mailbox;

use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Webkul\UVDesk\MailboxBundle\Utils\Imap\ConfigurationInterface as ImapConfiguration;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer\BaseConfiguration as MailerConfiguration;
use Webkul\UVDesk\MailboxBundle\Utils\Imap\AppConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\Imap\SimpleConfigurationInterface;

class Mailbox
{
    CONST TOKEN_RANGE = '12345';
    
    const APP_CONFIGURATION_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/Imap/AppConfiguration.php";
    const DEFAULT_CONFIGURATION_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/Imap/DefaultConfiguration.php";
    const SIMPLE_CONFIGURATION_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/Imap/SimpleConfiguration.php";

    const MAILBOX_CONFIGURATION_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/MailboxSettings.php";

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

        if ($imapConfiguration instanceof AppConfigurationInterface) {
            $imapTemplate = strtr(require self::APP_CONFIGURATION_TEMPLATE, [
                '[[ imap_client ]]' => $imapConfiguration->getClient(),
                '[[ imap_username ]]' => $imapConfiguration->getUsername(), 
            ]);
        } else if ($imapConfiguration instanceof SimpleConfigurationInterface) {
            $imapTemplate = strtr(require self::SIMPLE_CONFIGURATION_TEMPLATE, [
                '[[ imap_username ]]' => $imapConfiguration->getUsername(),
            ]);
        } else {
            $imapTemplate = strtr(require self::DEFAULT_CONFIGURATION_TEMPLATE, [
                '[[ imap_host ]]' => $imapConfiguration->getHost(),
                '[[ imap_username ]]' => $imapConfiguration->getUsername(),
                '[[ imap_password ]]' => $imapConfiguration->getPassword(),
            ]);
        }

        return strtr(require self::MAILBOX_CONFIGURATION_TEMPLATE, [
            '[[ id ]]' => $this->getId(),
            '[[ name ]]' => $this->getName(),
            '[[ status ]]' => $this->getIsEnabled() ? 'true' : 'false',
            '[[ delete_status ]]' => $this->getIsDeleted() ? 'true' : 'false',
            '[[ mailer_id ]]' => $mailerConfiguration ? $mailerConfiguration->getId() : '~',
            '[[ imap_settings ]]' => $imapTemplate,
        ]);
    }
}
