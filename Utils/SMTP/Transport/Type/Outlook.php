<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\Type;

use Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\TransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\ResolvedTransportConfigurationInterface;

class Outlook implements TransportConfigurationInterface, ResolvedTransportConfigurationInterface
{
    CONST CODE = 'outlook';
    CONST NAME = 'Outlook';
    CONST HOST = 'smtp.office365.com';
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
