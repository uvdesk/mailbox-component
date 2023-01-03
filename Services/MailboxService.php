<?php

namespace Webkul\UVDesk\MailboxBundle\Services;

use PhpMimeMailParser\Parser as EmailParser;
use Symfony\Component\Yaml\Yaml;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Thread;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Website;
use Webkul\UVDesk\MailboxBundle\Utils\Mailbox\Mailbox;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\HTMLFilter;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\SupportRole;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Webkul\UVDesk\MailboxBundle\Utils\MailboxConfiguration;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events as CoreWorkflowEvents;
use Webkul\UVDesk\MailboxBundle\Utils\Imap\Configuration as ImapConfiguration;
use Webkul\UVDesk\CoreFrameworkBundle\SwiftMailer\SwiftMailer as SwiftMailerService;
use Webkul\UVDesk\MailboxBundle\Workflow\Events as MaibloxWorkflowEvents;

class MailboxService
{
    const PATH_TO_CONFIG = '/config/packages/uvdesk_mailbox.yaml';

    private $mailboxCollection = [];

    public function __construct(ContainerInterface $container, RequestStack $requestStack, EntityManagerInterface $entityManager, SwiftMailerService $swiftMailer)
    {
        $this->container = $container;
		$this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->swiftMailer = $swiftMailer;
    }

    public function getPathToConfigurationFile()
    {
        return $this->container->get('kernel')->getProjectDir() . self::PATH_TO_CONFIG;
    }

    public function createConfiguration($params)
    {
        $configuration = new MailboxConfigurations\MailboxConfiguration($params);
        return $configuration ?? null;
    }

    public function parseMailboxConfigurations(bool $ignoreInvalidAttributes = false) 
    {
        $path = $this->getPathToConfigurationFile();

        if (!file_exists($path)) {
            throw new \Exception("File '$path' not found.");
        }

        // Read configurations from package config.
        $mailboxConfiguration = new MailboxConfiguration();
        $swiftmailerService = $this->swiftMailer;
        $swiftmailerConfigurations = $swiftmailerService->parseSwiftMailerConfigurations();

        foreach (Yaml::parse(file_get_contents($path))['uvdesk_mailbox']['mailboxes'] ?? [] as $id => $params) {
            // Swiftmailer Configuration
            $swiftmailerConfiguration = null;

            foreach ($swiftmailerConfigurations as $configuration) {
                if ($configuration->getId() == $params['smtp_server']['mailer_id']) {
                    $swiftmailerConfiguration = $configuration;
                    break;
                }
            }
            
            // IMAP Configuration
            ($imapConfiguration = ImapConfiguration::guessTransportDefinition($params['imap_server']['host']))
                ->setUsername($params['imap_server']['username'])
                ->setPassword($params['imap_server']['password']);

            // Mailbox Configuration
            ($mailbox = new Mailbox($id))
                ->setName($params['name'])
                ->setIsEnabled($params['enabled'])
                ->setIsDeleted(empty($params['deleted']) ? false : $params['deleted'])
                ->setImapConfiguration($imapConfiguration);
            
            if (!empty($swiftmailerConfiguration)) {
                $mailbox->setSwiftMailerConfiguration($swiftmailerConfiguration);
            } else if (!empty($params['smtp_server']['mailer_id']) && true === $ignoreInvalidAttributes) {
                $mailbox->setSwiftMailerConfiguration($swiftmailerService->createConfiguration('smtp', $params['smtp_server']['mailer_id']));
            }

            $mailboxConfiguration->addMailbox($mailbox);
        }

        return $mailboxConfiguration;
    }

    private function getLoadedEmailContentParser($emailContents = null, $cacheContent = true): ?EmailParser
    {
        if (empty($emailContents)) {
            return $this->emailParser ?? null;
        }

        $emailParser = new EmailParser();
        $emailParser
            ->setText($emailContents)
        ;

        if ($cacheContent) {
            $this->emailParser = $emailParser;
        }

        return $emailParser;
    }

    private function getRegisteredMailboxes()
    {
        if (empty($this->mailboxCollection)) {
            $this->mailboxCollection = array_map(function ($mailboxId) {
                return $this->container->getParameter("uvdesk.mailboxes.$mailboxId");
            }, $this->container->getParameter('uvdesk.mailboxes'));
        }

        return $this->mailboxCollection;
    }

    public function getRegisteredMailboxesById()
    {
        // Fetch existing content in file
        $filePath = $this->getPathToConfigurationFile();
        $file_content = file_get_contents($filePath);

        // Convert yaml file content into array and merge existing mailbox and new mailbox
        $file_content_array = Yaml::parse($file_content, 6);

        if ($file_content_array['uvdesk_mailbox']['mailboxes']) {
            foreach ($file_content_array['uvdesk_mailbox']['mailboxes'] as $key => $value) {
                $value['mailbox_id'] = $key;
                $mailboxCollection[] = $value;
            }
        }
        
        return $mailboxCollection ?? [];
    }

    public function getEmailAddresses($collection)
    {
        $formattedCollection = array_map(function ($emailAddress) {
            if (filter_var($emailAddress['address'], FILTER_VALIDATE_EMAIL)) {
                return $emailAddress['address'];
            }

            return null;
        }, (array) $collection);

        $filteredCollection = array_values(array_filter($formattedCollection));

        return count($filteredCollection) == 1 ? $filteredCollection[0] : $filteredCollection;
    }

    public function getMailboxByEmail($email)
    {
        foreach ($this->getRegisteredMailboxes() as $registeredMailbox) {
            if (strtolower($email) === strtolower($registeredMailbox['imap_server']['username'])) {
                return $registeredMailbox;
            }
        }

        throw new \Exception("No mailbox found for email '$email'");
    }
	
    private function searchticketSubjectRefrence($senderEmail, $messageSubject) {
        
        // Search Criteria: Find ticket based on subject
        if (!empty($senderEmail) && !empty($messageSubject)) {
            $threadRepository = $this->entityManager->getRepository(Thread::class);
            $ticket = $threadRepository->findTicketBySubject($senderEmail, $messageSubject);

            if ($ticket  != null) {
                return $ticket;
            }
        }

        return null;
    }

    private function searchExistingTickets(array $criterias = [])
    {
        if (empty($criterias)) {
            return null;
        }

        $ticketRepository = $this->entityManager->getRepository(Ticket::class);
        $threadRepository = $this->entityManager->getRepository(Thread::class);

        foreach ($criterias as $criteria => $criteriaValue) {
            if (empty($criteriaValue)) {
                continue;
            }

            switch ($criteria) {
                case 'messageId':
                    // Search Criteria 1: Find ticket by unique message id
                    $ticket = $ticketRepository->findOneByReferenceIds($criteriaValue);

                    if (!empty($ticket)) {
                        return $ticket;
                    } else {
                        $thread = $threadRepository->findOneByMessageId($criteriaValue);
        
                        if (!empty($thread)) {
                            return $thread->getTicket();
                        }
                    }
                    break;
                case 'inReplyTo':
                    // Search Criteria 2: Find ticket based on in-reply-to reference id

                    $repository = $this->entityManager->getRepository(Thread::class);
                    $ticket = $repository->findThreadByRefrenceId($criteriaValue);


                    if (!empty($ticket)) {
                        return $ticket;
                    } else {
                        $thread = $threadRepository->findOneByMessageId($criteriaValue);
        
                        if (!empty($thread)) {
                            return $thread->getTicket();
                        }
                    }
                    break;
                case 'referenceIds':
                    // Search Criteria 3: Find ticket based on reference id
                    // Break references into ind. message id collection, and iteratively 
                    // search for existing threads for these message ids.
                    $referenceIds = explode(' ', $criteriaValue);

                    foreach ($referenceIds as $messageId) {
                        $thread = $threadRepository->findOneByMessageId($messageId);

                        if (!empty($thread)) {
                            return $thread->getTicket();
                        }
                    }
                    break;
                default:
                    break;
            }
        }

        return null;
    }

    private function prepareResolvedEmailHeaders(EmailParser $emailParser): array
    {
        // Email headers with all sender/recipients details
        $emailHeaders = [
            'from' => $emailParser->getHeader('from') != false ? $emailParser->getHeader('from') : null, 
            'reply-to' => $emailParser->getHeader('reply-to') != false ? $emailParser->getHeader('reply-to') : null, 
            'to' => $emailParser->getHeader('to') != false ? $emailParser->getHeader('to') : null, 
            'cc' => $emailParser->getHeader('cc') != false ? $emailParser->getHeader('cc') : null, 
            'bcc' => $emailParser->getHeader('bcc') != false ? $emailParser->getHeader('bcc') : null, 
            'x-forwarded-to' => $emailParser->getHeader('x-forwarded-to') != false ? $emailParser->getHeader('x-forwarded-to') : null, 
            'delivered-to' => $emailParser->getHeader('delivered-to') != false ? $emailParser->getHeader('delivered-to') : null, 
        ];
    
        // If 'from' header is empty, use 'sender' header if provided instead
        if (empty($emailHeaders['from']) && $emailParser->getHeader('sender') != false) {
            $emailHeaders['from'] = $emailParser->getHeader('sender');
        }
        
        // Resolve & map only email addresses from email headers
        $resolvedEmailHeaders = [];
    
        foreach ($emailHeaders as $headerName => $headerContent) {
            $resolvedEmailHeaders[$headerName] = null;
            
            if (!empty($headerContent)) {
                $parsedEmailAddresses = mailparse_rfc822_parse_addresses($headerContent);
    
                $emailHeaders[$headerName] = $parsedEmailAddresses;
                $resolvedEmailHeaders[$headerName] = $this->getEmailAddresses($parsedEmailAddresses);
            }
        }

        return [$emailHeaders, $resolvedEmailHeaders];
    }
    
    public function processMail($emailContents)
    {
        $emailParser = $this->getLoadedEmailContentParser($emailContents);

        list($emailHeaders, $resolvedEmailHeaders) = $this->prepareResolvedEmailHeaders($emailParser);

        // Skip email processing if email is an auto-forwarded message to prevent infinite loop.
        if (empty($resolvedEmailHeaders['from'])) {
            // Skip email processing if no to-emails are specified
            return [
                'message' => "No sender email addresses were found while processing contents of email.", 
                'content' => [], 
            ];
        } else if (empty($resolvedEmailHeaders['to']) && empty($resolvedEmailHeaders['delivered-to']) && empty($resolvedEmailHeaders['cc'])) {
            // Skip email processing if no recipient emails are specified
            return [
                'message' => "No recipient email addresses were found while processing contents of email.", 
                'content' => [
                    'from' => !empty($resolvedEmailHeaders['from']) ? $resolvedEmailHeaders['from'] : null, 
                ], 
            ];
        } else {
            // Skip email if it is auto-generated to prevent looping of emails
            if ($emailParser->getHeader('precedence') || $emailParser->getHeader('x-autoreply') || $emailParser->getHeader('x-autorespond') || 'auto-replied' == $emailParser->getHeader('auto-submitted')) {
                return [
                    'message' => "Received an auto-forwarded email which can lead to possible infinite loop of email exchanges. Skipping email from further processing.", 
                    'content' => [
                        'from' => $resolvedEmailHeaders['from'] ?? null, 
                    ], 
                ];
            }

            $website = $this->entityManager->getRepository(Website::class)->findOneByCode('knowledgebase');

            // Skip email if sender email address is in block list
            if ($this->container->get('ticket.service')->isEmailBlocked($resolvedEmailHeaders['from'], $website)) {
                return [
                    'message' => "Received email where the sender email address is present in the block list. Skipping this email from further processing.", 
                    'content' => [
                        'from' => $resolvedEmailHeaders['from'], 
                    ], 
                ];
            }

            // Check for self-referencing
            // 1. Skip email processing if a mailbox is configured by the sender's address
            try {
                $this->getMailboxByEmail($resolvedEmailHeaders['from']);

                return [
                    'message' => "Received a self-referencing email where the sender email address matches one of the configured mailbox address. Skipping email from further processing.", 
                    'content' => [
                        'from' => $resolvedEmailHeaders['from'], 
                    ], 
                ];
            } catch (\Exception $e) { /* No mailboxes found */ }

            // 2. Skip email processing if a mailbox is configured by the reply-to email address
            try {
                if (!empty($resolvedEmailHeaders['reply-to'])) {
                    $this->getMailboxByEmail($resolvedEmailHeaders['reply-to']);

                    return [
                        'message' => "Received a self-referencing email where the reply-to email address matches one of the configured mailbox address. Skipping email from further processing.", 
                        'content' => [
                            'from' => $resolvedEmailHeaders['reply-to'], 
                        ], 
                    ];
                }
            } catch (\Exception $e) { /* No mailboxes found */ }
        }

        // Trigger email recieved event
        $event = new MaibloxWorkflowEvents\Email\EmailRecieved();
        $event
            ->setEmailHeaders($emailHeaders)
            ->setResolvedEmailHeaders($resolvedEmailHeaders)
        ;

        $this->container->get('event_dispatcher')->dispatch($event, 'uvdesk.automation.workflow.execute');

        $emailHeaders = $event->getEmailHeaders();
        $resolvedEmailHeaders = $event->getResolvedEmailHeaders();

        $senderEmailAddress = $resolvedEmailHeaders['from'];
        $senderName = trim(current(explode('@', $emailHeaders['from'][0]['display'])));

        $mailboxEmail = null;
        
        $mailboxEmailCandidates = array_merge((array) $resolvedEmailHeaders['to'], (array) $resolvedEmailHeaders['delivered-to'], (array) $resolvedEmailHeaders['cc']);
        $mailboxEmailCandidates = array_values(array_unique(array_filter($mailboxEmailCandidates)));

        foreach ($mailboxEmailCandidates as $emailAddress) {
            try {
                $mailbox = $this->getMailboxByEmail($emailAddress);

                if (!empty($mailbox)) {
                    $mailboxEmail = $emailAddress;

                    break;
                }
            } catch (\Exception $e) { /* No mailboxes found */ }
        }

        // Process Mail - References
        $mailData = [
            'name' => $senderName, 
            'from' => $senderEmailAddress, 
            'role' => 'ROLE_CUSTOMER', 
            'source' => 'email', 
            'createdBy' => 'customer', 
            'mailboxEmail' => $mailboxEmail, 
            'cc' => $resolvedEmailHeaders['cc'] ?? [], 
            'bcc' => $resolvedEmailHeaders['bcc'] ?? [], 
            'messageId' => $emailParser->getHeader('message-id') != false ? $emailParser->getHeader('message-id') : null, 
            'inReplyTo' => $emailParser->getHeader('in-reply-to') != false ? htmlspecialchars_decode($emailParser->getHeader('in-reply-to')) : null, 
            'referenceIds' => $emailParser->getHeader('references') != false ? htmlspecialchars_decode($emailParser->getHeader('references')) : null, 
            'subject' => $emailParser->getHeader('subject') != false ? $emailParser->getHeader('subject') : null, 
            'text' => $emailParser->getMessageBody('text'), 
            'htmlEmbedded' => $emailParser->getMessageBody('htmlEmbedded'), 
            'attachments' => $emailParser->getAttachments(), 
        ];

        // Format message content
        $htmlFilter = new HTMLFilter();

        $mailData['text'] = autolink($htmlFilter->addClassEmailReplyQuote($mailData['text']));
        $mailData['htmlEmbedded'] = autolink($htmlFilter->addClassEmailReplyQuote($mailData['htmlEmbedded']));
        
        $mailData['message'] = !empty($mailData['htmlEmbedded']) ? $mailData['htmlEmbedded'] : $mailData['text'];
        
        // Search for any existing tickets
        $ticket = $this->searchExistingTickets([
            'messageId' => $mailData['messageId'],
            'inReplyTo' => $mailData['inReplyTo'],
            'referenceIds' => $mailData['referenceIds'],
            'from' => $mailData['from'],
            'subject' => $mailData['subject'],
        ]);

        if (empty($ticket)) {
            $mailData['threadType'] = 'create';
            $mailData['referenceIds'] = $mailData['messageId'];

            // @TODO: Concatenate two tickets for same customer with same subject depending on settings
            $thread = $this->container->get('ticket.service')->createTicket($mailData);

            // Trigger ticket created event
            $event = new CoreWorkflowEvents\Ticket\Create();
            $event
                ->setTicket($thread->getTicket())
            ;

            $this->container->get('event_dispatcher')->dispatch($event, 'uvdesk.automation.workflow.execute');
        } else if (false === $ticket->getIsTrashed() && strtolower($ticket->getStatus()->getCode()) != 'spam' && !empty($mailData['inReplyTo'])) {
            $ticketRef = $this->entityManager->getRepository(Ticket::class)->findById($ticket->getId());
            $thread = $this->entityManager->getRepository(Thread::class)->findOneByMessageId($mailData['messageId']);

            $referenceIds = explode(' ', $ticketRef[0]->getReferenceIds());

            if (!empty($thread)) {
                // Thread with the same message id exists skip process.
                return [
                    'message' => "The contents of this email has already been processed.", 
                    'content' => [
                        'from' => !empty($mailData['from']) ? $mailData['from'] : null, 
                        'thread' => $thread->getId(), 
                        'ticket' => $ticket->getId(), 
                    ], 
                ];
            }

            if (in_array($mailData['messageId'], $referenceIds)) {
                // Thread with the same message id exists skip process.
                return [
                    'message' => "The contents of this email has already been processed.", 
                    'content' => [
                        'from' => !empty($mailData['from']) ? $mailData['from'] : null, 
                    ], 
                ];
            }

            if ($ticket->getCustomer() && $ticket->getCustomer()->getEmail() == $mailData['from']) {
                // Reply from customer
                $user = $ticket->getCustomer();

                $mailData['user'] = $user;
                $userDetails = $user->getCustomerInstance()->getPartialDetails();
            } else if ($this->entityManager->getRepository(Ticket::class)->isTicketCollaborator($ticket, $mailData['from'])){
            	// Reply from collaborator
                $user = $this->entityManager->getRepository(User::class)->findOneByEmail($mailData['from']);

                $mailData['user'] = $user;
                $mailData['createdBy'] = 'collaborator';
                $userDetails = $user->getCustomerInstance()->getPartialDetails();
            } else {
                $user = $this->entityManager->getRepository(User::class)->findOneByEmail($mailData['from']);
                
                if (!empty($user) && null != $user->getAgentInstance()) {
                    $mailData['user'] = $user;
                    $mailData['createdBy'] = 'agent';
                    $userDetails = $user->getAgentInstance()->getPartialDetails();
                } else {
                    // Add user as a ticket collaborator
                    if (empty($user)) {
                        // Create a new user instance with customer support role
                        $role = $this->entityManager->getRepository(SupportRole::class)->findOneByCode('ROLE_CUSTOMER');

                        $user = $this->container->get('user.service')->createUserInstance($mailData['from'], $mailData['name'], $role, [
                            'source' => 'email',
                            'active' => true
                        ]);
                    }

                    $mailData['user'] = $user;
                    $userDetails = $user->getCustomerInstance()->getPartialDetails();

                    if (false == $this->entityManager->getRepository(Ticket::class)->isTicketCollaborator($ticket, $mailData['from'])) {
                        $ticket->addCollaborator($user);

                        $this->entityManager->persist($ticket);
                        $this->entityManager->flush();

                        $ticket->lastCollaborator = $user;
                        
                        $event = new CoreWorkflowEvents\Ticket\Collaborator();
                        $event
                            ->setTicket($ticket)
                        ;

                        $this->container->get('event_dispatcher')->dispatch($event, 'uvdesk.automation.workflow.execute');
                    }
                }
            }

            $mailData['threadType'] = 'reply';
            $mailData['fullname'] = $userDetails['name'];
            
            $thread = $this->container->get('ticket.service')->createThread($ticket, $mailData);
            
            if ($thread->getThreadType() == 'reply') {
                if ($thread->getCreatedBy() == 'customer') {
                    $event = new CoreWorkflowEvents\Ticket\CustomerReply();
                    $event
                        ->setTicket($ticket)
                    ;
                }  else if ($thread->getCreatedBy() == 'collaborator') {
                    $event = new CoreWorkflowEvents\Ticket\CollaboratorReply();
                    $event
                        ->setTicket($ticket)
                    ;
                } else {
                    $event = new CoreWorkflowEvents\Ticket\AgentReply();
                    $event
                        ->setTicket($ticket)
                    ;
                }
            }

            // Trigger thread reply event
            $this->container->get('event_dispatcher')->dispatch($event, 'uvdesk.automation.workflow.execute');
        } else if (false === $ticket->getIsTrashed() && strtolower($ticket->getStatus()->getCode()) != 'spam' && empty($mailData['inReplyTo'])) {
            return [
                'message' => "The contents of this email has already been processed.", 
                'content' => [
                    'from' => !empty($mailData['from']) ? $mailData['from'] : null, 
                    'thread' => !empty($thread) ? $thread->getId() : null, 
                    'ticket' => !empty($ticket) ? $ticket->getId() : null, 
                ], 
            ];
        }

        return [
            'message' => "Inbound email processsed successfully.", 
            'content' => [
                'from' => !empty($mailData['from']) ? $mailData['from'] : null, 
                'thread' => !empty($thread) ? $thread->getId() : null, 
                'ticket' => !empty($ticket) ? $ticket->getId() : null, 
            ], 
        ];
    }
}
