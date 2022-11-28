<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\Imap\Transport;

use Webkul\UVDesk\MailboxBundle\Utils\Imap\ConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\Imap\SimpleConfigurationInterface;

class Webhook implements ConfigurationInterface, SimpleConfigurationInterface
{
    CONST CODE = 'webhook';
    CONST NAME = 'Webhook';

    private $username;

    public static function getCode()
    {
        return self::CODE;
    }

    public static function getName()
    {
        return self::NAME;
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
