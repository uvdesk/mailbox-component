<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport;

interface DefaultTransportConfigurationInterface
{
    public function __construct($host);

    public static function getCode();
    
    public static function getName();
    
    public function setHost($host);
    
    public function getHost();
    
    public function setUsername($username);
    
    public function getUsername();

    public function setPassword($password);
    
    public function getPassword();
}
