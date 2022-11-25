<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\Imap\Transport;

use Webkul\UVDesk\MailboxBundle\Utils\Imap\ConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\Imap\AppConfigurationInterface;

class OutlookModernAuth implements ConfigurationInterface, AppConfigurationInterface
{
    CONST CODE = 'outlook_oauth';
    CONST NAME = 'Outlook Modern Auth';

    private $client;
    private $username;

    public static function getCode()
    {
        return self::CODE;
    }

    public static function getName()
    {
        return self::NAME;
    }

    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }
}
