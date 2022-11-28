<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\Imap;

interface AppConfigurationInterface
{
    public static function getCode();
    
    public static function getName();

    public function setClient($client);
    
    public function getClient();
    
    public function setUsername($username);
    
    public function getUsername();
}
