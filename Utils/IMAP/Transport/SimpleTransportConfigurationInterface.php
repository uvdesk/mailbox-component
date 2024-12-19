<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport;

interface SimpleTransportConfigurationInterface
{
    public static function getCode();
    
    public static function getName();
    
    public function setUsername($username);
    
    public function getUsername();
}
