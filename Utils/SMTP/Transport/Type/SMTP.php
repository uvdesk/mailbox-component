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
    protected $username = null;
    protected $password = null;

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
    }

    public function getHost()
    {
        return $this->host;
    }
    
    public function setPort($port)
    {
        $this->port = $port;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }
}
