<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\Type;

use Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\TransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\AppTransportConfigurationInterface;

class OutlookModernAuth implements TransportConfigurationInterface, AppTransportConfigurationInterface
{
    CONST CODE = 'outlook_oauth';
    CONST NAME = 'Outlook Modern Auth';
    CONST TYPE = 'microsoftgraph';

    private $client = null;
    protected $username = null;

    public static function getCode()
    {
        return self::CODE;
    }

    public static function getName()
    {
        return self::NAME;
    }

    public static function getType()
    {
        return self::TYPE;
    }

    public function getClient()
    {
        return $this->client;
    }
    
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }
}
