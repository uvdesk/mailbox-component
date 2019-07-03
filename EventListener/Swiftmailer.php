<?php

namespace Webkul\UVDesk\MailboxBundle\EventListener;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\RequestStack;
use Webkul\UVDesk\MailboxBundle\Utils\Mailbox\Mailbox;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreBundle\EventListener\EventListenerInterface;
use Webkul\UVDesk\CoreBundle\SwiftMailer\Event\ConfigurationRemovedEvent;
use Webkul\UVDesk\CoreBundle\SwiftMailer\Event\ConfigurationUpdatedEvent;

class Swiftmailer implements EventListenerInterface
{
    protected $container;
    protected $requestStack;

    public final function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->requestStack = $requestStack;
    }

    public function onSwiftMailerConfigurationUpdated(ConfigurationUpdatedEvent $event)
    {
        $isUpdateRequiredFlag = false;
        $updatedConfiguration = $event->getUpdatedSwiftMailerConfiguration();
        $existingConfiguration = $event->getExistingSwiftMailerConfiguration();
               
        if ($updatedConfiguration->getId() == $existingConfiguration->getId()) {
            // We only need to update if the swiftmailer configuration's id has changed
            // or if it has been disabled.

            return;
        }
        $newMailerId = $updatedConfiguration->getId();
        $oldMailerId = $existingConfiguration->getId();
        $this->updateUvdeskYmlFile($oldMailerId, $newMailerId);

        $mailboxConfiguration = $this->container->get('uvdesk.mailbox')->parseMailboxConfigurations(true);

        foreach ($mailboxConfiguration->getMailboxes() as $existingMailbox) {
            if ($existingMailbox->getSwiftmailerConfiguration()->getId() == $existingConfiguration->getId()) {
                // Disable mailbox and update configuration
                $mailbox = new Mailbox($existingMailbox->getId());
                $mailbox->setName($existingMailbox->getName())
                    ->setIsEnabled($existingMailbox->getIsEnabled())
                    ->setImapConfiguration($existingMailbox->getImapConfiguration())
                    ->setSwiftMailerConfiguration($updatedConfiguration);
                
                $isUpdateRequiredFlag = true;
                $mailboxConfiguration->removeMailbox($existingMailbox);
                $mailboxConfiguration->addMailbox($mailbox);
            }
        }

        if (true === $isUpdateRequiredFlag) {
            file_put_contents($this->container->get('uvdesk.mailbox')->getPathToConfigurationFile(), (string) $mailboxConfiguration);
        }
        
        return;
    }

    public function onSwiftMailerConfigurationRemoved(ConfigurationRemovedEvent $event)
    {
        $isUpdateRequiredFlag = false;
        $configuration = $event->getSwiftMailerConfiguration(); 
        $mailboxConfiguration = $this->container->get('uvdesk.mailbox')->parseMailboxConfigurations();

        foreach ($mailboxConfiguration->getMailboxes() as $existingMailbox) {
            if(!empty($existingMailbox->getSwiftmailerConfiguration())){
                if ($existingMailbox->getSwiftmailerConfiguration()->getId() == $configuration->getId()) {
                // Disable mailbox and update configuration
                    $mailbox = new Mailbox($existingMailbox->getId());
                    $mailbox->setName($existingMailbox->getName())
                        ->setIsEnabled(false)
                        ->setImapConfiguration($existingMailbox->getImapConfiguration());

                    $isUpdateRequiredFlag = true;
                    $mailboxConfiguration->removeMailbox($existingMailbox);
                    $mailboxConfiguration->addMailbox($mailbox);
                }
            }
        }

        if (true === $isUpdateRequiredFlag) {
            file_put_contents($this->container->get('uvdesk.mailbox')->getPathToConfigurationFile(), (string) $mailboxConfiguration);
        }
        $oldMailerId = $configuration->getId();
        $newMailerId = null;
        // updating uvdesk.yaml file.
        $this->updateUvdeskYmlFile($oldMailerId, $newMailerId);
        return;
    }
    
    public function updateUvdeskYmlFile($oldMailerId, $newMailerId = null)
    {
        $filePath = $this->container->get('kernel')->getProjectDir() . '/config/packages/uvdesk.yaml';
        $file_content = file_get_contents($filePath);
        $file_content_array = Yaml::parse($file_content, 6); 
        $result = $file_content_array['uvdesk']['support_email'];
       
        if($result['mailer_id'] == $oldMailerId){
            $templatePath = $this->container->get('kernel')->getProjectDir() . '/vendor/uvdesk/core-framework/Templates/uvdesk.php';
            
            $malierIdValue = is_null($newMailerId) ? '~' : $newMailerId;

            $file_data_array = strtr(require $templatePath, [
                '{{ SITE_URL }}' => $file_content_array['uvdesk']['site_url'],
                '{{ SUPPORT_EMAIL_ID }}' => $file_content_array['uvdesk']['support_email']['id'] ,
                '{{ SUPPORT_EMAIL_NAME }}' => $file_content_array['uvdesk']['support_email']['name'],
                '{{ SUPPORT_EMAIL_MAILER_ID }}'  => $malierIdValue,
            ]);
            // updating contents of uvdesk.yaml file.
            file_put_contents($filePath, $file_data_array);
        }
        return;
    }
}
