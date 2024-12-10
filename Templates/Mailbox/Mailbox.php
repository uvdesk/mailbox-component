<?php

$template = <<<TEMPLATE
uvdesk_mailbox:
    emails: ~
        # Often Reply emails like from gmail contains extra and redundant previous mail data.
        # This data can be removed by adding delimiter i.e. specific line before each reply. 
        # delimiter: '<-- Please add content above this line -->'
        # enable_delimiter: true

    # Specify id of the mailbox you want to use as default for sending emails when no other "configured" mailbox is found.
    # Applies to all emails sent through the system & in cases of tickets where the original source mailbox of the created
    # ticket is no longer available.
TEMPLATE;

if ($this->getDefaultMailbox() == null) {
    $template .= <<<TEMPLATE

    default_mailbox: ~

TEMPLATE;
} else {
    $template .= <<<TEMPLATE

    default_mailbox: [[ DEFAULT_MAILBOX ]]

TEMPLATE;
}

if (!empty($mailboxes)) {
    $template .= <<<TEMPLATE

    # Configure your mailboxes here
    mailboxes:
[[ MAILBOXES ]]
TEMPLATE;
} else {
    $template .= <<<TEMPLATE

    # Configure your mailboxes here
        mailboxes:
        # mailbox_id:
        #     name: 'Mailbox'
        #     enabled: true
        #     disable_outbound_emails: false
        #     use_strict_mode: false
        
        #     # Incoming email settings
        #     # IMAP settings to use for fetching emails from mailbox
        #     imap_server:
        #         host: ~
        #         username: ~
        #         password: ~

        #     # Outgoing email settings
        #     # SMTP settings to use for sending emails from mailbox
        #     smtp_server:
        #         host: ~
        #         port: ~
        #         username: ~
        #         password: ~
        #         sender_address: ~
        
        #         # For app transport method
        #         type: ~
        #         client: ~
        #     smtp_swift_mailer_server:
        #         mailer_id: ~
TEMPLATE;
}

return strtr($template, [
    '[[ DEFAULT_MAILBOX ]]' => $this->getDefaultMailbox() ? $this->getDefaultMailbox()->getId() : null, 
    '[[ MAILBOXES ]]'       => $mailboxes, 
]);

?>