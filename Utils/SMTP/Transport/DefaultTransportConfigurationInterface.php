<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport;

interface DefaultTransportConfigurationInterface
{
    public function setHost($host);

    public function getHost();

    public function setPort($port);
    
    public function getPort();

    public function setUsername($username);
    
    public function getUsername();

    public function setPassword($password);
    
    public function getPassword();

    public function setSenderAddress($senderAddress);
    
    public function getSenderAddress();
}
