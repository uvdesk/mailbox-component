<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\Type;

use Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\TransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\SimpleTransportConfigurationInterface;

class Webhook implements TransportConfigurationInterface, SimpleTransportConfigurationInterface
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
