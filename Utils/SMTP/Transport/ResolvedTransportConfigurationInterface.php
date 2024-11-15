<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport;

interface ResolvedTransportConfigurationInterface
{
    public function getHost();
    
    public function getPort();

    public function setUsername($username);
    
    public function getUsername();

    public function setPassword($password);
    
    public function getPassword();
}
