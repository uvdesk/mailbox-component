<?php

$template = <<<TEMPLATE
        [[ id ]]:
            name: [[ name ]]
            enabled: [[ status ]]
            disable_outbound_emails: [[ disable_outbound_emails ]]
            use_strict_mode: [[ use_strict_mode ]]

TEMPLATE;

if (!empty($imapTemplate)) {
    $template .= <<<TEMPLATE

            # Incoming email settings
            # IMAP settings to use for fetching emails from mailbox
[[ imap_settings ]]

TEMPLATE;
}

if (!empty($smtpTemplate)) {
    $template .= <<<TEMPLATE

            # Outgoing email settings
            # SMTP settings to use for sending emails from mailbox
[[ smtp_settings ]]

TEMPLATE;
}

return strtr($template, [
    '[[ id ]]' => $this->getId(),
    '[[ name ]]' => $this->getName(),
    '[[ disable_outbound_emails ]]' => $this->getIsEmailDeliveryDisabled() ? 'true' : 'false',
    '[[ status ]]' => $this->getIsEnabled() ? 'true' : 'false',
    '[[ use_strict_mode ]]' => $this->getIsStrictModeEnabled() ? 'true' : 'false',
    '[[ smtp_settings ]]' => $smtpTemplate,
    '[[ imap_settings ]]' => $imapTemplate,
]);

?>