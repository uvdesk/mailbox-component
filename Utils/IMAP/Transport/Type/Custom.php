<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\Type;

use Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\TransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\CustomTransportConfigurationInterface;

class Custom implements TransportConfigurationInterface, CustomTransportConfigurationInterface
{
    CONST CODE = 'custom';
    CONST NAME = 'Custom';

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
