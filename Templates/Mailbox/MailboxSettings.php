<?php

return <<<TEMPLATE
        [[ id ]]:
            name: [[ name ]]
            enabled: [[ status ]]
            disable_outbound_emails: [[ disable_outbound_emails ]]
            use_strict_mode: [[ use_strict_mode ]]

            # Incoming email settings
            # IMAP settings to use for fetching emails from mailbox
[[ imap_settings ]]

            # Outgoing email settings
            # SMTP settings to use for sending emails from mailbox
[[ smtp_settings ]]

TEMPLATE;

?>