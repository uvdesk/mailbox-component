<?php

return <<<TEMPLATE
            imap_server:
                host: '[[ imap_host ]]'
                username: [[ imap_username ]]
                password: [[ imap_password ]]
TEMPLATE;

?>