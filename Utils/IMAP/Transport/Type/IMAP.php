<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\Type;

use Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\TransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\DefaultTransportConfigurationInterface;

class IMAP implements TransportConfigurationInterface, DefaultTransportConfigurationInterface
{
    CONST CODE = 'imap';
    CONST NAME = 'IMAP';

    private $host;
    private $username;
    private $password;

    public function __construct($host)
    {
        $this->setHost($host);

        return $this;
    }

    public static function getCode()
    {
        return self::CODE;
    }

    public static function getName()
    {
        return self::NAME;
    }

    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    public function getHost()
    {
        return $this->host;
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
