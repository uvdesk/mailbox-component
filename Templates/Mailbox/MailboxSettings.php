<?php

return <<<TEMPLATE
        [[ id ]]:
            name: [[ name ]]
            enabled: [[ status ]]
            deleted: [[ delete_status ]]

            # Outgoing email settings
            # Mailer settings to use for sending emails from mailbox
            smtp_server: 
                mailer_id: [[ mailer_id ]]

            # Incoming email settings
            # IMAP settings to use for fetching emails from mailbox
[[ imap_settings ]]

TEMPLATE;

?>