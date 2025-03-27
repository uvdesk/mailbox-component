<?php

namespace Webkul\UVDesk\MailboxBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class UVDeskExtension extends Extension
{
    public function getAlias()
    {
        return 'uvdesk_mailbox';
    }

    public function getConfiguration(array $configs, ContainerBuilder $container)
    {
        return new Configuration();
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $services = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config/services'));
        
        $services->load('services.yaml');
        
        // Register automations conditionally if AutomationBundle has been added as an dependency.
        if (array_key_exists('UVDeskAutomationBundle', $container->getParameter('kernel.bundles'))) {
            $services->load('automations.yaml');
        }
        
        // Load bundle configurations
        $configuration = $this->getConfiguration($configs, $container);

        foreach ($this->processConfiguration($configuration, $configs) as $param => $value) {
            switch ($param) {
                case 'emails':
                    foreach ($value as $field => $fieldValue) {
                        $container->setParameter("uvdesk.emails.$field", $fieldValue);
                    }

                    break;
                case 'default_mailbox':
                    $container->setParameter("uvdesk.default_mailbox", $value);

                    break;
                case 'mailboxes':
                    $container->setParameter("uvdesk.mailboxes", array_keys($value));

                    foreach ($value as $mailboxId => $mailboxDetails) {
                        $container->setParameter("uvdesk.mailboxes.$mailboxId", $mailboxDetails);
                    }

                    break;
                default:
                    break;
            }
        }
    }
}
