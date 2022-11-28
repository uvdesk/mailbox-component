<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\Imap;

interface SimpleConfigurationInterface
{
    public static function getCode();
    
    public static function getName();
    
    public function setUsername($username);
    
    public function getUsername();
}
