CHANGELOG for 1.0.x
===================

This changelog references any relevant changes introduced in 1.0 minor versions.

* 1.0.4 (2019-01-25)
    * **Issue #38:** Issue for imap host field when add host inside qoutes ' '.
    * **Issue #28:** Error while edit disable mailbox.
    * **Issue #50:** Email setting are not being update in production mode.
    * **Issue #51:** Duplicate entry for ticket when running refresh command second     time.

* 1.0.3 (2019-11-15)
    * **Issue #46:** IMAP not creating tickets
    * **Misc. Updates:**
        * Included Github issue templates
        * Updated composer dependencies & set minimum required php version to 7.2

* 1.0.2 (2019-10-22)
    * **Misc. Updates:**
        * Use https when available while refreshing mailboxes via CLI
        * Updated README.md with link to the official gitter chat for uvdesk/mailbox-component

* 1.0.1 (2019-10-15)
    * **Misc. Updates:**
        * Only users with admin level privileges can configure mailbox settings

* 1.0.0 (Released on 2019-10-09)
    * **Issue #44:** Misc. fixes (raised by anmol107)
    * **Issue #14:** duplicate swiftmailer created with same email (raised by vaishaliwebkul)
    * **Issue #42:** SwiftMailer SVG update (raised by vaishaliwebkul)
    * **Issue #41:** Added Mailbox entry in Search  list (raised by vaishaliwebkul)
    * **Issue #40:** Add search bar component items (raised by shubhwebkul)
    * **Issue #39:** Error when update swiftmailer configuration (raised by vaishaliwebkul)
    * **Issue #32:** IMAP host field is not mandatory (raised by vaishaliwebkul)
    * **Issue #35:** update template emailSettings.html (raised by vaishaliwebkul)
    * **Issue #31:** Mailbox fields missing when edit mailbox for imap transport (raised by vaishaliwebkul)
    * **Issue #15:** Swiftmailer gets deleted for setup mailbox (raised by vaishaliwebkul)
    * **Issue #16:** updated swiftmailer id not update into uvdesk.yaml (raised by vaishaliwebkul)
    * **Issue #33:** Confirm box must be appear while deleting a mailbox configuration (raised by vaishaliwebkul)
    * **Issue #30:** uvdesk.yml file update and issues (raised by kumarSaurabh27)
    * **Issue #22:** Swiftmailer update with blank mailer_id (raised by vaishaliwebkul)
    * **Issue #10:** Define maximum character length of mailbox name (raised by vaishaliwebkul)
    * **Issue #20:** Port not define for mailbox when set transport as IMAP (raised by vaishaliwebkul)
    * **Issue #27:** Resolve issue(thread was not creating) while creating the ticket via … (raised by papnoisanjeev)
    * **Issue #26:** Feature added to show progress in Mailbox refresh command and Issues. (raised by kumarSaurabh27)
    * **Issue #24:** Update Prefix in uvdesk.yaml (raised by vaishaliwebkul)
    * **Issue #18:** yahoo configuration issue with user password  (raised by vaishaliwebkul)
    * **Issue #13:** Default site url set when update uvdesk.yaml[email settings] (raised by vaishaliwebkul)
    * **Issue #23:** mailbox-component issue-10 (raised by kumarSaurabh27)
    * **Issue #9:** Mailbox id not accept integer values  (raised by vaishaliwebkul)
    * **Issue #12:** Error while deleting swiftmailer (raised by vaishaliwebkul)
    * **Issue #8:** Mailbox fields not sanitized properly (raised by vaishaliwebkul)
    * **Issue #21:** Always set automatically created mailbox id  (raised by vaishaliwebkul)
    * **Issue #19:** Update message if mailbox configuration not found (raised by vaishaliwebkul)
    * **Issue #11:** blocked email check when creating ticket through mail (raised by papnoisanjeev)
    * **Issue #7:** resolved issue (raised by shubhwebkul)
    * **Issue #6:** Mailbox setup validation updates (raised by shubhwebkul)
    * **Issue #5:** Added a function in mailbox service for mailbox collection (raised by papnoisanjeev)
    * **Issue #4:** mailbox updates (raised by shubhwebkul)
    * **Issue #3:** resolved issues (raised by shubhwebkul)
    * **Issue #2:** resolved issues while configuring mailbox and refactored code (raised by shubhwebkul)
    * **Issue #1:** Decoupled Mailbox Component (raised by akshaywebkul)
