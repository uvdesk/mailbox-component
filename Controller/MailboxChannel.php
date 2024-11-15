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
use Webkul\UVDesk\CoreFrameworkBundle\SwiftMailer\SwiftMailer as SwiftMailerService;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\MailboxBundle\Utils\IMAP;
use Webkul\UVDesk\MailboxBundle\Utils\SMTP;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Microsoft\MicrosoftApp;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Microsoft\MicrosoftAccount;

class MailboxChannel extends AbstractController
{
    private $mailboxService;
    private $translator;
    private $swiftMailer;
    private $userService;
    private $entityManager;

    public function __construct(UserService $userService, MailboxService $mailboxService, TranslatorInterface $translator, SwiftMailerService $swiftMailer, EntityManagerInterface $entityManager)
    {
        $this->userService = $userService;
        $this->mailboxService = $mailboxService;
        $this->translator = $translator;
        $this->swiftMailer = $swiftMailer;
        $this->entityManager = $entityManager;
    }

    public function loadMailboxes()
    {
        if (!$this->userService->isAccessAuthorized('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        return $this->render('@UVDeskMailbox//listConfigurations.html.twig');
    }

    public function createMailboxConfiguration(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        if ('microsoft') {
            $microsoftAppCollection = $this->entityManager->getRepository(MicrosoftApp::class)->findBy(['isEnabled' => true, 'isVerified' => true]);
            $microsoftAccountCollection = $this->entityManager->getRepository(MicrosoftAccount::class)->findAll();

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

                $smtpConfiguration = null;
                $imapConfiguration = null;

                // IMAP Configuration
                if (!empty($params['imap']['transport'])) {
                    $imapConfiguration = IMAP\Configuration::createTransportDefinition($params['imap']['transport'], !empty($params['imap']['host']) ? trim($params['imap']['host'], '"') : null);

                    if ($imapConfiguration instanceof IMAP\Transport\AppTransportConfigurationInterface) {
                        if ($params['imap']['transport'] == 'outlook_oauth') {
                            $microsoftAccount = $this->entityManager->getRepository(MicrosoftAccount::class)->findOneById($params['imap']['username']);

                            if (empty($microsoftAccount)) {
                                $this->addFlash('warning', 'No configuration details were found for the provided microsoft account.');

                                return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
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
                                'microsoftAppCollection' => $microsoftAppCollection,
                                'microsoftAccountCollection' => $microsoftAccountCollection,
                            ]);
                        }
                    } else if ($imapConfiguration instanceof IMAP\Transport\SimpleTransportConfigurationInterface) {
                        $imapConfiguration
                            ->setUsername($params['imap']['username'])
                        ;
                    } else {
                        $imapConfiguration
                            ->setUsername($params['imap']['username'])
                            ->setPassword(urlencode($params['imap']['password']))
                        ;
                    }
                }

                // SMTP Configuration
                if (!empty($params['smtp']['transport'])) {
                    $smtpConfiguration = SMTP\Configuration::createTransportDefinition($params['smtp']['transport'], !empty($params['smtp']['host']) ? trim($params['smtp']['host'], '"') : null);

                    if ($smtpConfiguration instanceof SMTP\Transport\AppTransportConfigurationInterface) {
                        if ($params['smtp']['transport'] == 'outlook_oauth') {
                            $microsoftAccount = $this->entityManager->getRepository(MicrosoftAccount::class)->findOneById($params['smtp']['username']);

                            if (empty($microsoftAccount)) {
                                $this->addFlash('warning', 'No configuration details were found for the provided microsoft account.');

                                return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
                                    'microsoftAppCollection' => $microsoftAppCollection,
                                    'microsoftAccountCollection' => $microsoftAccountCollection,
                                ]);
                            }

                            $params['smtp']['username'] = $microsoftAccount->getEmail();
                            $params['smtp']['client'] = $microsoftAccount->getMicrosoftApp()->getClientId();

                            $smtpConfiguration
                                ->setClient($params['smtp']['client'])
                                ->setUsername($params['smtp']['username'])
                            ;
                        } else {
                            $this->addFlash('warning', 'The resolved SMTP configuration is not configured for any valid available app.');

                            return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
                                'microsoftAppCollection' => $microsoftAppCollection,
                                'microsoftAccountCollection' => $microsoftAccountCollection,
                            ]);
                        }
                    } else if ($smtpConfiguration instanceof SMTP\Transport\ResolvedTransportConfigurationInterface) {
                        $smtpConfiguration
                            ->setUsername($params['smtp']['username'])
                            ->setPassword(urlencode($params['smtp']['password']))
                        ;
                    } else {
                        $smtpConfiguration
                            ->setHost($params['smtp']['host'])
                            ->setPort((int) $params['smtp']['port'])
                            ->setUsername($params['smtp']['username'])
                            ->setPassword(urlencode($params['smtp']['password']))
                            ->setSenderAddress(!empty($params['smtp']['senderAddress']) ? $params['smtp']['senderAddress'] : null)
                        ;
                    }
                }

                if (empty($imapConfiguration) && empty($smtpConfiguration)) {
                    $this->addFlash('warning', 'Invalid mailbox details provided. Mailbox needs to have at least IMAP or SMTP settings defined.');
                } else {
                    $mailboxConfiguration = $mailboxService->parseMailboxConfigurations();

                    $mailbox = new Mailbox(!empty($params['id']) ? $params['id'] : null);
                    $mailbox
                        ->setName($params['name'])
                        ->setIsEnabled(!empty($params['isEnabled']) && 'on' == $params['isEnabled'] ? true : false)
                        ->setIsEmailDeliveryDisabled(!empty($params['isEmailDeliveryDisabled']) && 'on' == $params['isEmailDeliveryDisabled'] ? true : false)
                    ;

                    if (!empty($imapConfiguration)) {
                        $mailbox
                            ->setImapConfiguration($imapConfiguration)
                        ;
                    }

                    if (!empty($smtpConfiguration)) {
                        $mailbox
                            ->setSmtpConfiguration($smtpConfiguration)
                        ;
                    }

                    $mailboxConfiguration->addMailbox($mailbox);

                    if (!empty($params['isDefault']) && 'on' == $params['isDefault']) {
                        $mailboxConfiguration
                            ->setDefaultMailbox($mailbox)
                        ;
                    }

                    file_put_contents($mailboxService->getPathToConfigurationFile(), (string) $mailboxConfiguration);

                    $this->addFlash('success', $translator->trans('Mailbox successfully created.'));

                    return new RedirectResponse($this->generateUrl('helpdesk_member_mailbox_settings'));
                }
            }

            return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
                'microsoftAppCollection' => $microsoftAppCollection,
                'microsoftAccountCollection' => $microsoftAccountCollection,
            ]);
        }else {
            $swiftmailerConfigurationCollection = $this->swiftMailer->parseSwiftMailerConfigurations();

            if ($request->getMethod() == 'POST') {
                $params = $request->request->all();

                // IMAP Configuration
                if (!empty($params['imap']['transport'])) {
                    ($imapConfiguration = ImapConfiguration::createTransportDefinition($params['imap']['transport'], !empty($params['imap']['host']) ? trim($params['imap']['host'], '"') : null))
                        ->setUsername($params['imap']['username'])
                        ->setPassword(base64_encode($params['imap']['password']));
                }

                // Swiftmailer Configuration
                if (!empty($params['swiftmailer_id'])) {
                    foreach ($swiftmailerConfigurationCollection as $configuration) {
                        if ($configuration->getId() == $params['swiftmailer_id']) {
                            $swiftmailerConfiguration = $configuration;
                            break;
                        }
                    }
                }

                if (!empty($imapConfiguration) && !empty($swiftmailerConfiguration)) {
                    $mailboxService = $this->mailboxService;
                    $mailboxConfiguration = $mailboxService->parseMailboxConfigurations();

                    ($mailbox = new Mailbox(!empty($params['id']) ? $params['id'] : null))
                        ->setName($params['name'])
                        ->setIsEnabled(!empty($params['isEnabled']) && 'on' == $params['isEnabled'] ? true : false)
                        ->setIsDeleted(!empty($params['isDeleted']) && 'on' == $params['isDeleted'] ? true : false)
                        ->setImapConfiguration($imapConfiguration)
                        ->setSwiftMailerConfiguration($swiftmailerConfiguration);

                    $mailboxConfiguration->addMailbox($mailbox);

                    file_put_contents($mailboxService->getPathToConfigurationFile(), (string) $mailboxConfiguration);

                    $this->addFlash('success', $this->translator->trans('Mailbox successfully created.'));
                    return new RedirectResponse($this->generateUrl('helpdesk_member_mailbox_settings'));
                }
            }

            return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
                'swiftmailerConfigurations' => $swiftmailerConfigurationCollection,
            ]);
        }
    }

    public function updateMailboxConfiguration($id, Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        if('microsoft') {
            $mailboxConfiguration = $mailboxService->parseMailboxConfigurations();
            $mailbox = $mailboxConfiguration->getMailboxById($id);

            if (empty($mailbox)) {
                return new Response('', 404);
            }

            $microsoftAppCollection = $this->entityManager->getRepository(MicrosoftApp::class)->findBy(['isEnabled' => true, 'isVerified' => true]);
            $microsoftAccountCollection = $this->entityManager->getRepository(MicrosoftAccount::class)->findAll();

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

                $smtpConfiguration = null;
                $imapConfiguration = null;

                // IMAP Configuration
                if (!empty($params['imap']['transport'])) {
                    $imapConfiguration = IMAP\Configuration::createTransportDefinition($params['imap']['transport'], !empty($params['imap']['host']) ? trim($params['imap']['host'], '"') : null);

                    if ($imapConfiguration instanceof IMAP\Transport\AppTransportConfigurationInterface) {
                        if ($params['imap']['transport'] == 'outlook_oauth') {
                            $microsoftAccount = $this->entityManager->getRepository(MicrosoftAccount::class)->findOneById($params['imap']['username']);

                            if (empty($microsoftAccount)) {
                                $this->addFlash('warning', 'No configuration details were found for the provided microsoft account.');

                                return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
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
                                'microsoftAppCollection' => $microsoftAppCollection,
                                'microsoftAccountCollection' => $microsoftAccountCollection,
                            ]);
                        }
                    } else if ($imapConfiguration instanceof IMAP\Transport\SimpleTransportConfigurationInterface) {
                        $imapConfiguration
                            ->setUsername($params['imap']['username'])
                        ;
                    } else {
                        $imapConfiguration
                            ->setUsername($params['imap']['username'])
                            ->setPassword(urlencode($params['imap']['password']))
                        ;
                    }
                }

                // SMTP Configuration
                if (!empty($params['smtp']['transport'])) {
                    $smtpConfiguration = SMTP\Configuration::createTransportDefinition($params['smtp']['transport'], !empty($params['smtp']['host']) ? trim($params['smtp']['host'], '"') : null);

                    if ($smtpConfiguration instanceof SMTP\Transport\AppTransportConfigurationInterface) {
                        if ($params['smtp']['transport'] == 'outlook_oauth') {
                            $microsoftAccount = $this->entityManager->getRepository(MicrosoftAccount::class)->findOneById($params['smtp']['username']);

                            if (empty($microsoftAccount)) {
                                $this->addFlash('warning', 'No configuration details were found for the provided microsoft account.');

                                return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
                                    'microsoftAppCollection' => $microsoftAppCollection,
                                    'microsoftAccountCollection' => $microsoftAccountCollection,
                                ]);
                            }

                            $params['smtp']['username'] = $microsoftAccount->getEmail();
                            $params['smtp']['client'] = $microsoftAccount->getMicrosoftApp()->getClientId();

                            $smtpConfiguration
                                ->setClient($params['smtp']['client'])
                                ->setUsername($params['smtp']['username'])
                            ;
                        } else {
                            $this->addFlash('warning', 'The resolved SMTP configuration is not configured for any valid available app.');

                            return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
                                'microsoftAppCollection' => $microsoftAppCollection,
                                'microsoftAccountCollection' => $microsoftAccountCollection,
                            ]);
                        }
                    } else if ($smtpConfiguration instanceof SMTP\Transport\ResolvedTransportConfigurationInterface) {
                        $smtpConfiguration
                            ->setUsername($params['smtp']['username'])
                            ->setPassword(urlencode($params['smtp']['password']))
                        ;
                    } else {
                        $smtpConfiguration
                            ->setHost($params['smtp']['host'])
                            ->setPort((int) $params['smtp']['port'])
                            ->setUsername($params['smtp']['username'])
                            ->setPassword(urlencode($params['smtp']['password']))
                            ->setSenderAddress(!empty($params['smtp']['senderAddress']) ? $params['smtp']['senderAddress'] : null)
                        ;
                    }
                }

                if (empty($imapConfiguration) && empty($smtpConfiguration)) {
                    $this->addFlash('warning', 'Invalid mailbox details provided. Mailbox needs to have at least IMAP or SMTP settings defined.');
                } else {
                    if (!empty($params['id']) && $params['id'] != $mailbox->getId()) {
                        $mailboxConfiguration->removeMailbox($mailbox);

                        $mailbox = new Mailbox($params['id']);
                    }

                    $mailbox
                        ->setName($params['name'])
                        ->setIsEnabled(!empty($params['isEnabled']) && 'on' == $params['isEnabled'] ? true : false)
                        ->setIsEmailDeliveryDisabled(!empty($params['isEmailDeliveryDisabled']) && 'on' == $params['isEmailDeliveryDisabled'] ? true : false)
                    ;

                    if (!empty($imapConfiguration)) {
                        $mailbox
                            ->setImapConfiguration($imapConfiguration)
                        ;
                    }

                    if (!empty($smtpConfiguration)) {
                        $mailbox
                            ->setSmtpConfiguration($smtpConfiguration)
                        ;
                    }

                    $mailboxConfiguration->addMailbox($mailbox);

                    if (!empty($params['isDefault']) && 'on' == $params['isDefault']) {
                        $mailboxConfiguration
                            ->setDefaultMailbox($mailbox)
                        ;
                    }

                    file_put_contents($mailboxService->getPathToConfigurationFile(), (string) $mailboxConfiguration);

                    $this->addFlash('success', $translator->trans('Mailbox successfully updated.'));

                    return new RedirectResponse($this->generateUrl('helpdesk_member_mailbox_settings'));
                }
            }

            $defaultMailbox = $mailboxConfiguration->getDefaultMailbox();

            return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
                'mailbox' => $mailbox,
                'isDefaultMailbox' => !empty($defaultMailbox) && $defaultMailbox->getId() == $mailbox->getId(),
                'microsoftAppCollection' => $microsoftAppCollection,
                'microsoftAccountCollection' => $microsoftAccountCollection,
            ]);

        } else {
            $mailboxService = $this->mailboxService;
            $existingMailboxConfiguration = $mailboxService->parseMailboxConfigurations();
            $swiftmailerConfigurationCollection = $this->swiftMailer->parseSwiftMailerConfigurations();

            foreach ($existingMailboxConfiguration->getMailboxes() as $configuration) {
                if ($configuration->getId() == $id) {
                    $mailbox = $configuration;
                    break;
                }
            }

            if (empty($mailbox)) {
                return new Response('', 404);
            }

            if ($request->getMethod() == 'POST') {
                $params = $request->request->all();
                // IMAP Configuration
                if (!empty($params['imap']['transport'])) {
                    ($imapConfiguration = ImapConfiguration::createTransportDefinition($params['imap']['transport'], !empty($params['imap']['host']) ? trim($params['imap']['host'], '"') : null))
                        ->setUsername($params['imap']['username'])
                        ->setPassword(base64_encode($params['imap']['password']));
                }

                // Swiftmailer Configuration
                if (!empty($params['swiftmailer_id'])) {
                    foreach ($swiftmailerConfigurationCollection as $configuration) {
                        if ($configuration->getId() == $params['swiftmailer_id']) {
                            $swiftmailerConfiguration = $configuration;

                            break;
                        }
                    }
                }

                if (!empty($imapConfiguration) && !empty($swiftmailerConfiguration)) {
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
                                ->setSwiftMailerConfiguration($swiftmailerConfiguration);

                            $mailboxConfiguration->addMailbox($mailbox);

                            continue;
                        }

                        $mailboxConfiguration->addMailbox($configuration);
                    }

                    file_put_contents($mailboxService->getPathToConfigurationFile(), (string) $mailboxConfiguration);

                    $this->addFlash('success', $this->translator->trans('Mailbox successfully updated.'));

                    return new RedirectResponse($this->generateUrl('helpdesk_member_mailbox_settings'));
                }
            }

            return $this->render('@UVDeskMailbox//manageConfigurations.html.twig', [
                'mailbox' => $mailbox ?? null,
                'swiftmailerConfigurations' => $swiftmailerConfigurationCollection,
            ]);

        }
    }
}
