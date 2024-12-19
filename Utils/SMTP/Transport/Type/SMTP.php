<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\Type;

use Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\TransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\DefaultTransportConfigurationInterface;

class SMTP implements TransportConfigurationInterface, DefaultTransportConfigurationInterface
{
    CONST CODE = 'smtp';
    CONST NAME = 'SMTP';
    
    private $host = null;
    private $port = null;
    private $username = null;
    private $password = null;
    private $senderAddress = null;

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
    
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    public function getPort()
    {
        return $this->port;
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

    public function setSenderAddress($senderAddress)
    {
        $this->senderAddress = $senderAddress;

        return $this;
    }

    public function getSenderAddress()
    {
        return $this->senderAddress;
    }
}
