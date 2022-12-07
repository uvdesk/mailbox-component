<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\Type;

use Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\TransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\ResolvedTransportConfigurationInterface;

class Yahoo implements TransportConfigurationInterface, ResolvedTransportConfigurationInterface
{
    CONST CODE = 'yahoo';
    CONST NAME = 'Yahoo';
    CONST HOST = 'smtp.mail.yahoo.com';
    CONST PORT = '587';

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

    public function getHost()
    {
        return self::HOST;
    }
    
    public function getPort()
    {
        return self::PORT;
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
