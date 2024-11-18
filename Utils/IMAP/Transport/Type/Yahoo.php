<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\Type;

use Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\TransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\ResolvedTransportConfigurationInterface;

class Yahoo implements TransportConfigurationInterface, ResolvedTransportConfigurationInterface
{
    CONST CODE = 'yahoo';
    CONST NAME = 'Yahoo';
    CONST HOST = '{imap.mail.yahoo.com:993/imap/ssl}INBOX';

    private $username;
    private $password;
    
    public static function getCode()
    {
        return self::CODE;
    }

    public static function getName()
    {
        return self::NAME;
    }

    public static function getHost()
    {
        return self::HOST;
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

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }
}
