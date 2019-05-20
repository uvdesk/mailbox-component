<?php

namespace Webkul\UVDesk\MailboxBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreBundle\EventListener\EventListenerInterface;
use Webkul\UVDesk\CoreBundle\SwiftMailer\Event\ConfigurationRemovedEvent;

class Mailbox implements EventListenerInterface
{
    protected $container;
    protected $requestStack;

    public final function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->requestStack = $requestStack;
    }

    public function onSwiftMailerConfigurationRemoved(ConfigurationRemovedEvent $event)
    {
        $configuration = $event->getSwiftMailerConfiguration();
        
        return;
    }
}
