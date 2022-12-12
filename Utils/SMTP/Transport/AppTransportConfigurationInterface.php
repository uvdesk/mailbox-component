<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport;

interface AppTransportConfigurationInterface
{
    public static function getType();

    public function setClient($client);
    
    public function getClient();
    
    public function setUsername($username);
    
    public function getUsername();
}
