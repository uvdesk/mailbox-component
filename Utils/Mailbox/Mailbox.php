<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\Mailbox;

use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Webkul\UVDesk\MailboxBundle\Utils\IMAP;
use Webkul\UVDesk\MailboxBundle\Utils\SMTP;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\SwiftMailer\BaseConfiguration as SwiftMailerConfiguration;

class Mailbox
{
    CONST TOKEN_RANGE = '12345';

    const IMAP_APP_CONFIGURATION_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/IMAP/AppConfiguration.php";
    const IMAP_DEFAULT_CONFIGURATION_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/IMAP/DefaultConfiguration.php";
    const IMAP_SIMPLE_CONFIGURATION_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/IMAP/SimpleConfiguration.php";

    const SMTP_APP_CONFIGURATION_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/SMTP/AppConfiguration.php";
    const SMTP_DEFAULT_CONFIGURATION_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/SMTP/DefaultConfiguration.php";

    const MAILBOX_CONFIGURATION_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/MailboxSettings.php";
    const SWIFT_MAILER_CONFIGURATION_TEMPLATE = __DIR__ . "/../../Templates/Mailbox/SwiftMailerSettings.php";

    private $id = null;
    private $name = null;
    private $isEnabled = true;
    private $isEmailDeliveryDisabled = false;
    private $isStrictModeEnabled = false;
    private $imapConfiguration = null;
    private $smtpConfiguration = null;
    private $swiftMailerConfiguration = null;

    public function __construct($id = null)
    {
        $this->setId($id ?? sprintf("mailbox_%s", TokenGenerator::generateToken(4, self::TOKEN_RANGE)));
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

    public function setIsEmailDeliveryDisabled(bool $isEmailDeliveryDisabled)
    {
        $this->isEmailDeliveryDisabled = $isEmailDeliveryDisabled;

        return $this;
    }

    public function getIsEmailDeliveryDisabled() : bool
    {
        return $this->isEmailDeliveryDisabled;
    }

    public function setIsStrictModeEnabled($isStrictModeEnabled)
    {
        $this->isStrictModeEnabled = $isStrictModeEnabled;

        return $this;
    }

    public function getIsStrictModeEnabled()
    {
        return $this->isStrictModeEnabled;
    }

    public function setImapConfiguration(IMAP\Transport\TransportConfigurationInterface $imapConfiguration)
    {
        $this->imapConfiguration = $imapConfiguration;

        return $this;
    }

    public function getImapConfiguration() : ?IMAP\Transport\TransportConfigurationInterface
    {
        return $this->imapConfiguration;
    }

    public function setSmtpConfiguration(SMTP\Transport\TransportConfigurationInterface $smtpConfiguration)
    {
        $this->smtpConfiguration = $smtpConfiguration;

        return $this;
    }

    public function getSmtpConfiguration() : ?SMTP\Transport\TransportConfigurationInterface
    {
        return $this->smtpConfiguration;
    }

    public function setSwiftMailerConfiguration(SwiftMailerConfiguration $swiftMailerConfiguration)
    {
        $this->swiftMailerConfiguration = $swiftMailerConfiguration;

        return $this;
    }

    public function getSwiftMailerConfiguration() : ?SwiftMailerConfiguration
    {
        return $this->swiftMailerConfiguration;
    }

    public function __toString()
    {
        $imapConfiguration = $this->getImapConfiguration();
        $smtpConfiguration = $this->getSmtpConfiguration();
        $swiftMailerConfiguration = $this->getSwiftMailerConfiguration();

        $imapTemplate = '';

        if (! empty($imapConfiguration)) {
            if ($imapConfiguration instanceof IMAP\Transport\AppTransportConfigurationInterface) {
                $imapTemplate = strtr(require self::IMAP_APP_CONFIGURATION_TEMPLATE, [
                    '[[ imap_client ]]'   => $imapConfiguration->getClient(),
                    '[[ imap_username ]]' => $imapConfiguration->getUsername(),
                ]);
            } else if ($imapConfiguration instanceof IMAP\Transport\SimpleTransportConfigurationInterface) {
                $imapTemplate = strtr(require self::IMAP_SIMPLE_CONFIGURATION_TEMPLATE, [
                    '[[ imap_username ]]' => $imapConfiguration->getUsername(),
                ]);
            } else {
                $imapTemplate = strtr(require self::IMAP_DEFAULT_CONFIGURATION_TEMPLATE, [
                    '[[ imap_host ]]'     => $imapConfiguration->getHost(),
                    '[[ imap_username ]]' => $imapConfiguration->getUsername(),
                    '[[ imap_password ]]' => $imapConfiguration->getPassword(),
                ]);
            }
        }

        $smtpTemplate = '';

        if (!empty($smtpConfiguration)) {
            if ($smtpConfiguration instanceof SMTP\Transport\AppTransportConfigurationInterface) {
                $smtpTemplate = strtr(require self::SMTP_APP_CONFIGURATION_TEMPLATE, [
                    '[[ client ]]'   => $smtpConfiguration->getClient(),
                    '[[ username ]]' => $smtpConfiguration->getUsername(),
                    '[[ type ]]'     => $smtpConfiguration->getType(),
                ]);
            } else {
                $smtpTemplate = (require self::SMTP_DEFAULT_CONFIGURATION_TEMPLATE);
            }
        }
        
        $swiftMailerTemplate = '';

        if (! empty($swiftMailerConfiguration)) {
            $swiftMailerTemplate = strtr(require self::SWIFT_MAILER_CONFIGURATION_TEMPLATE, [
                '[[ swiftMailer_id ]]' => $swiftMailerConfiguration->getId(),
            ]);
        }

        return require self::MAILBOX_CONFIGURATION_TEMPLATE;
    }
}
