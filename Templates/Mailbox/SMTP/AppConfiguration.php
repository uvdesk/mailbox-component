<?php

$template = <<<TEMPLATE
            smtp_server:
                client: [[ client ]]
                username: [[ username ]]
                type: [[ type ]]
TEMPLATE;

$template .= <<<TEMPLATE


TEMPLATE;

return strtr($template, [
    '[[ client ]]' => $smtpConfiguration->getClient(),
    '[[ username ]]' => $smtpConfiguration->getUsername(), 
    '[[ type ]]' => $smtpConfiguration->getType(),
]);

?>