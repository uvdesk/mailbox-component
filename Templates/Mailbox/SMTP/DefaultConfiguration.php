<?php

$template = <<<TEMPLATE
            smtp_server:
                host: '[[ host ]]'
                port: [[ port ]]
                username: [[ username ]]
                password: [[ password ]]
TEMPLATE;

if (method_exists($smtpConfiguration, 'getSenderAddress') && $smtpConfiguration->getSenderAddress() != null) {
    $template .= <<<TEMPLATE

                sender_address: [[ sender_address ]]
TEMPLATE;
}

$template .= <<<TEMPLATE


TEMPLATE;

return strtr($template, [
    '[[ host ]]'           => $smtpConfiguration->getHost(),
    '[[ username ]]'       => $smtpConfiguration->getUsername(),
    '[[ password ]]'       => $smtpConfiguration->getPassword(),
    '[[ port ]]'           => $smtpConfiguration->getPort(),
    '[[ sender_address ]]' => method_exists($smtpConfiguration, 'getSenderAddress') ? $smtpConfiguration->getSenderAddress() : null,
]);

?>