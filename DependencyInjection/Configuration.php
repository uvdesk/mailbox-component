<?php

namespace Webkul\UVDesk\MailboxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('knp_doctrine_behaviors');
        $treeBuilder->getRootNode('uvdesk_mailbox')
            ->children()
                ->node('emails', 'array')
                    ->children()
                        ->node('delimiter', 'scalar')->defaultValue('<-- Please add content above this line -->')->end()
                        ->node('enable_delimiter', 'boolean')->defaultValue(false)->end()
                    ->end()
                ->end()
                ->node('default_mailbox', 'scalar')->defaultValue(null)->end()
                ->node('mailboxes', 'array')
                    ->arrayPrototype()
                        ->children()
                            ->node('name', 'scalar')->cannotBeEmpty()->end()
                            ->node('enabled', 'boolean')->defaultTrue()->end()
                            ->node('disable_outbound_emails', 'boolean')->defaultFalse()->end()
                            ->node('use_strict_mode', 'boolean')->defaultFalse()->end()
                            ->node('imap_server', 'array')
                                ->children()
                                    ->node('host', 'scalar')->cannotBeEmpty()->end()
                                    ->node('username', 'scalar')->cannotBeEmpty()->end()
                                    ->node('client', 'scalar')->end()
                                    ->node('password', 'scalar')->end()
                                    ->node('type', 'scalar')->end()
                                ->end()
                            ->end() 
                            ->node('smtp_server', 'array')
                                ->children()
                                    ->node('host', 'scalar')->cannotBeEmpty()->end()
                                    ->node('port', 'scalar')->end()
                                    ->node('username', 'scalar')->cannotBeEmpty()->end()
                                    ->node('client', 'scalar')->end()
                                    ->node('password', 'scalar')->end()
                                    ->node('type', 'scalar')->end()
                                    ->node('sender_address', 'scalar')->defaultValue(null)->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
