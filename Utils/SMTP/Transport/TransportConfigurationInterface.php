<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport;

interface TransportConfigurationInterface
{
    public static function getCode();

    public static function getName();
}
