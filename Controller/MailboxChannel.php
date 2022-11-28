<?php

namespace Webkul\UVDesk\MailboxBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Webkul\UVDesk\MailboxBundle\Utils\Mailbox\Mailbox;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\MailboxBundle\Utils\MailboxConfiguration;
use Webkul\UVDesk\MailboxBundle\Utils\Imap\Configuration as ImapConfiguration;
use Webkul\UVDesk\MailboxBundle\Services\MailboxService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Mailer\MailerService;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\MailboxBundle\Utils\Imap\AppConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\Imap\SimpleConfigurationInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\MicrosoftApp;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\MicrosoftAccount;

class MailboxChannel extends AbstractController
{
    public function loadMailboxes(UserService $userService)
    {
        if (!$userService->isAccessAuthorized('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        return $this->render('@UVDeskMailbox//listConfigurations.html.twig');
    }
    
    public function createMailboxConfiguration(Request $request, EntityManagerInterface $entityManager, UserService $userService, MailboxService $mailboxService, MailerService $mailerService, TranslatorInterface $translator)
    {
        if (!$userService->isAccessAuthorized('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $mailerConfigurationCollection = $mailerService->parseMailerConfigurations();

        $microsoftAppCollection = $entityManager->getRepository(MicrosoftApp::class)->findBy(['isEnabled' => true, 'isVerified' => true]);
        $microsoftAccountCollection = $entityManager->getRepository(MicrosoftAccount::class)->findAll();

        $microsoftAppCollection = array_map(function ($microsoftApp) {
            return [
                'id' => $microsoftApp->getId(), 
                'name' => $microsoftApp->getName(), 
            ];
        }, $microsoftAppCollection);

        $microsoftAccountCollection = array_map(function ($microsoftAccount) {
            return [
                'id' => $microsoftAccount->getId(), 
                'name' => $microsoftAccount->getName(), 
                'email' => $microsoftAccount->getEmail(), 
            ];
        }, $microsoftAccountCollection);

        if ($request->getMethod() == 'POST') {
            $params = $request->request->all();

            // IMAP Configuration
            if (!empty($params['imap']['transport'])) {
                $imapConfiguration = ImapConfiguration::createTransportDefinition($params['imap']['transport'], !empty($params['imap']['host']) ? trim($params['imap']['host'], '"') : null);

                if ($imapConfiguration instanceof AppConfigurationInterface) {
                    if ($params['imap']['transport'] == 'outlook_oauth') {
                        $microsoftAccount = $entityManager->getRepository(MicrosoftAccount::class)->findOneById($params['imap']['username']);
        
                        if (empty($microsoftAccount)) {
                            $this->addFlash('warning', 'No configuration details were found for the provided microsoft account.');
        
                            return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
                                'mailerConfigurations' => $mailerConfigurationCollection, 
                                'microsoftAppCollection' => $microsoftAppCollection, 
                                'microsoftAccountCollection' => $microsoftAccountCollection, 
                            ]);
                        }
        
                        $params['imap']['username'] = $microsoftAccount->getEmail();
                        $params['imap']['client'] = $microsoftAccount->getMicrosoftApp()->getClientId();
                        
                        $imapConfiguration
                            ->setClient($params['imap']['client'])
                            ->setUsername($params['imap']['username'])
                        ;
                    } else {
                        $this->addFlash('warning', 'The resolved IMAP configuration is not configured for any valid available app.');
        
                        return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
                            'mailerConfigurations' => $mailerConfigurationCollection, 
                            'microsoftAppCollection' => $microsoftAppCollection, 
                            'microsoftAccountCollection' => $microsoftAccountCollection, 
                        ]);
                    }
                } else if ($imapConfiguration instanceof SimpleConfigurationInterface) {
                    $imapConfiguration
                        ->setUsername($params['imap']['username'])
                    ;
                } else {
                    $imapConfiguration
                        ->setUsername($params['imap']['username'])
                        ->setPassword(base64_encode($params['imap']['password']))
                    ;
                }
            }

            // Mailer Configuration
            if (!empty($params['mailer_id'])) {
                foreach ($mailerConfigurationCollection as $configuration) {
                    if ($configuration->getId() == $params['mailer_id']) {
                        $mailerConfiguration = $configuration;

                        break;
                    }
                }
            }

            if (!empty($imapConfiguration) && !empty($mailerConfiguration)) {
                $mailboxConfiguration = $mailboxService->parseMailboxConfigurations();

                $mailbox = new Mailbox(!empty($params['id']) ? $params['id'] : null);
                $mailbox
                    ->setName($params['name'])
                    ->setIsEnabled(!empty($params['isEnabled']) && 'on' == $params['isEnabled'] ? true : false)
                    ->setIsDeleted(!empty($params['isDeleted']) && 'on' == $params['isDeleted'] ? true : false)
                    ->setImapConfiguration($imapConfiguration)
                    ->setMailerConfiguration($mailerConfiguration)
                ;

                $mailboxConfiguration->addMailbox($mailbox);

                file_put_contents($mailboxService->getPathToConfigurationFile(), (string) $mailboxConfiguration);

                $this->addFlash('success', $translator->trans('Mailbox successfully created.'));

                return new RedirectResponse($this->generateUrl('helpdesk_member_mailbox_settings'));
            }
        }

        return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
            'mailerConfigurations' => $mailerConfigurationCollection, 
            'microsoftAppCollection' => $microsoftAppCollection, 
            'microsoftAccountCollection' => $microsoftAccountCollection, 
        ]);
    }

    public function updateMailboxConfiguration($id, Request $request, EntityManagerInterface $entityManager, UserService $userService, MailboxService $mailboxService, MailerService $mailerService, TranslatorInterface $translator)
    {
        if (!$userService->isAccessAuthorized('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }
        
        $existingMailboxConfiguration = $mailboxService->parseMailboxConfigurations();
        $mailerConfigurationCollection = $mailerService->parseMailerConfigurations();

        foreach ($existingMailboxConfiguration->getMailboxes() as $configuration) {
            if ($configuration->getId() == $id) {
                $mailbox = $configuration;

                break;
            }
        }

        if (empty($mailbox)) {
            return new Response('', 404);
        }

        $microsoftAppCollection = $entityManager->getRepository(MicrosoftApp::class)->findBy(['isEnabled' => true, 'isVerified' => true]);
        $microsoftAccountCollection = $entityManager->getRepository(MicrosoftAccount::class)->findAll();

        $microsoftAppCollection = array_map(function ($microsoftApp) {
            return [
                'id' => $microsoftApp->getId(), 
                'name' => $microsoftApp->getName(), 
            ];
        }, $microsoftAppCollection);

        $microsoftAccountCollection = array_map(function ($microsoftAccount) {
            return [
                'id' => $microsoftAccount->getId(), 
                'name' => $microsoftAccount->getName(), 
                'email' => $microsoftAccount->getEmail(), 
            ];
        }, $microsoftAccountCollection);

        if ($request->getMethod() == 'POST') {
            $params = $request->request->all();
            
            // IMAP Configuration
            if (!empty($params['imap']['transport'])) {
                $imapConfiguration = ImapConfiguration::createTransportDefinition($params['imap']['transport'], !empty($params['imap']['host']) ? trim($params['imap']['host'], '"') : null);
                $imapConfiguration
                    ->setUsername($params['imap']['username'])
                    ->setPassword(base64_encode($params['imap']['password']))
                ;
            }

            // Mailer Configuration
            if (!empty($params['mailer_id'])) {
                foreach ($mailerConfigurationCollection as $configuration) {
                    if ($configuration->getId() == $params['mailer_id']) {
                        $mailerConfiguration = $configuration;

                        break;
                    }
                }
            }

            if (!empty($imapConfiguration) && !empty($mailerConfiguration)) {
                $mailboxConfiguration = new MailboxConfiguration();
                
                foreach ($existingMailboxConfiguration->getMailboxes() as $configuration) {
                    if ($mailbox->getId() == $configuration->getId()) {
                        if (empty($params['id'])) {
                            $mailbox = new Mailbox();
                        } else if ($mailbox->getId() != $params['id']) {
                            $mailbox = new Mailbox($params['id']);
                        }

                        $mailbox
                            ->setName($params['name'])
                            ->setIsEnabled(!empty($params['isEnabled']) && 'on' == $params['isEnabled'] ? true : false)
                            ->setIsDeleted(!empty($params['isDeleted']) && 'on' == $params['isDeleted'] ? true : false)
                            ->setImapConfiguration($imapConfiguration)
                            ->setMailerConfiguration($mailerConfiguration)
                        ;

                        $mailboxConfiguration->addMailbox($mailbox);

                        continue;
                    }

                    $mailboxConfiguration->addMailbox($configuration);
                }

                file_put_contents($mailboxService->getPathToConfigurationFile(), (string) $mailboxConfiguration);

                $this->addFlash('success', $translator->trans('Mailbox successfully updated.'));
                
                return new RedirectResponse($this->generateUrl('helpdesk_member_mailbox_settings'));
            }
        }

        return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
            'mailbox' => $mailbox ?? null, 
            'mailerConfigurations' => $mailerConfigurationCollection, 
            'microsoftAppCollection' => $microsoftAppCollection, 
            'microsoftAccountCollection' => $microsoftAccountCollection, 
        ]);
    }
}
