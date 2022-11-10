<?php

namespace Webkul\UVDesk\MailboxBundle\Console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RefreshMailboxCommand extends Command
{
    private $endpoint;

    public function __construct(ContainerInterface $container, EntityManagerInterface $entityManager)
    {
        $this->container = $container;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('uvdesk:refresh-mailbox');
        $this->setDescription('Check if any new emails have been received and process them into tickets');

        $this->addArgument('emails', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, "Email address of the mailboxes you wish to update");
        $this->addOption('timestamp', 't', InputOption::VALUE_REQUIRED, "Fetch messages no older than the given timestamp");
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $router = $this->container->get('router');
        $useSecureConnection = $this->isSecureConnectionAvailable();

        $router->getContext()->setHost($this->container->getParameter('uvdesk.site_url'));
        $router->getContext()->setScheme(false === $useSecureConnection ? 'http' : 'https');

        $this->endpoint = $router->generate('helpdesk_member_mailbox_notification', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Sanitize emails
        $mailboxEmailCollection = array_map(function ($email) {
            return filter_var($email, FILTER_SANITIZE_EMAIL);
        }, $input->getArgument('emails'));
       
        // Stop execution if no valid emails have been specified
        if (empty($mailboxEmailCollection)) {
            if (false === $input->getOption('no-interaction')) {
                $output->writeln("\n <comment>No valid mailbox emails specified.</comment>\n");
            }

            return Command::INVALID;
        }

        // Process mailboxes
        $timestamp = new \DateTime(sprintf("-%u minutes", (int) ($input->getOption('timestamp') ?: 1440)));
        
        foreach ($mailboxEmailCollection as $mailboxEmail) {
            $output->writeln("\n# Retrieving mailbox configuration details for <info>$mailboxEmail</info>:\n");

            try {
                $mailbox = $this->container->get('uvdesk.mailbox')->getMailboxByEmail($mailboxEmail);

                if (false == $mailbox['enabled']) {
                    if (false === $input->getOption('no-interaction')) {
                        $output->writeln("  <comment>Error: Mailbox for email </comment><info>$mailboxEmail</info><comment> is not enabled.</comment>");
                    }
    
                    continue;
                } else if (empty($mailbox['imap_server'])) {
                    if (false === $input->getOption('no-interaction')) {
                        $output->writeln("  <comment>Error: No imap configurations defined for email </comment><info>$mailboxEmail</info><comment>.</comment>");
                    }
    
                    continue;
                }
            } catch (\Exception $e) {
                if (false == $input->getOption('no-interaction')) {
                    $output->writeln("  <comment>Error: Mailbox for email </comment><info>$mailboxEmail</info><comment> not found.</comment>");

                    // return Command::INVALID;
                }

                continue;
            }

            try {
                $this->refreshMailbox(
                    $mailbox['imap_server']['host'], 
                    $mailbox['imap_server']['username'], 
                    base64_decode($mailbox['imap_server']['password']), 
                    $timestamp, 
                    $output, 
                    $mailbox
                );
            } catch (\Exception $e) {
                $output->writeln("  <comment>An unexpected error occurred: " . $e->getMessage() . "</comment>");
            }
        }

        $output->writeln("");

        return Command::SUCCESS;
    }

    public function refreshMailbox($server_host, $server_username, $server_password, \DateTime $timestamp, OutputInterface $output, $mailbox)
    {
        $output->writeln("  - Establishing connection with mailbox");

        $imap = imap_open($server_host, $server_username, $server_password);

        if ($imap) {
            $timeSpan = $timestamp->format('d F Y');
            $output->writeln("  - Fetching all emails since <comment>$timeSpan</comment>");

            $emailCollection = imap_search($imap, 'SINCE "' . $timestamp->format('d F Y') . '"');

            if (is_array($emailCollection)) {
                $emailCount = count($emailCollection);

                $output->writeln("  - Found a total of <info>$emailCount</info> emails in mailbox since <comment>$timeSpan</comment>");
                $output->writeln("\n  # Processing all found emails iteratively:");
                $output->writeln("\n    <bg=black;fg=bright-white>API</> <options=underscore>" . $this->endpoint . "</>\n");

                $counter = 1;

                foreach ($emailCollection as $id => $messageNumber) {
                    $output->writeln("    - <comment>Processing email</comment> <info>$counter</info> <comment>of</comment> <info>$emailCount</info>:");
                    
                    $message = imap_fetchbody($imap, $messageNumber, "");
                    list($response, $responseCode, $responseErrorMessage) = $this->parseInboundEmail($message, $output);

                    if ($responseCode == 200) {
                        $output->writeln("\n      <bg=green;fg=bright-white;options=bold>200</> " . $response['message'] . "\n");
                    } else {
                        if (!empty($responseErrorMessage)) {
                            $output->writeln("\n      <bg=red;fg=white;options=bold>ERROR</> <fg=red>$responseErrorMessage</>\n");
                        } else {
                            $output->writeln("\n      <bg=red;fg=white;options=bold>ERROR</> <fg=red>" . $response['message'] . "</>\n");
                        }
                    }
                    
                    if (true == $mailbox['deleted']) {
                        imap_delete($imap, $messageNumber);
                    }
                    
                    $counter ++;
                }

                $output->writeln("  - <info>Mailbox refreshed successfully!</info>");

                if (true == $mailbox['deleted']) {
                    imap_expunge($imap);
                    imap_close($imap,CL_EXPUNGE);
                }
            }
        }

        return;
    }

    public function parseInboundEmail($message, $output)
    {
        $curlHandler = curl_init();
        
        curl_setopt($curlHandler, CURLOPT_HEADER, 0);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_POST, 1);
        curl_setopt($curlHandler, CURLOPT_URL, $this->endpoint);
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, http_build_query(['email' => $message]));

        $curlResponse = curl_exec($curlHandler);
        
        $response = json_decode($curlResponse, true);
        $responseCode = curl_getinfo($curlHandler, CURLINFO_HTTP_CODE);
        $responseErrorMessage = null;

        if (curl_errno($curlHandler) || $responseCode != 200) {
            $responseErrorMessage = curl_error($curlHandler);
        }

        curl_close($curlHandler);

        return [$response, $responseCode, $responseErrorMessage];
    }

    protected function isSecureConnectionAvailable()
    {
        $headers = [CURLOPT_NOBODY => true, CURLOPT_HEADER => false];
        $curlHandler = curl_init('https://' . $this->container->getParameter('uvdesk.site_url'));

        curl_setopt_array($curlHandler, $headers);
        curl_exec($curlHandler);

        $isSecureRequestAvailable = curl_errno($curlHandler) === 0 ? true : false;
        curl_close($curlHandler);

        return $isSecureRequestAvailable;
    }
}
