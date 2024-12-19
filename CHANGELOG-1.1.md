CHANGELOG for 1.1.x
===================

This changelog references any relevant changes introduced in 1.1 minor versions.

* 1.1.4 (2024-12-19)
    * Microsoft Modern App related updates.
    * License and support email address updates.
    * Code refactoring.

* 1.1.3 (2023-06-12)
    * Update: Dropped dependency on uvdesk/composer-plugin in support of symfony/flex

* 1.1.2 (2022-11-10)
    * Feature: Add improved log reports when refreshing mailboxes

* 1.1.1 (2022-09-13)
    * Bug Fixes: Entity reference updates and other miscellaneous bug fixes

* 1.1.0 (2022-03-23)
    * Feature: Improved compatibility with PHP 8 and Symfony 5 components
    * Bug #92: Check if replyTo headers is provided while processing mail content (vipin-shrivastava)
    * Bug #90: Fix ErrorException thrown on empty $mailData['replyTo']. (fiftyz)
    * Bug #88: Update mailbox configuration form placeholder values (vipin-shrivastava)
    * Bug #87: Update search criteria when looking up relevant threads based on inReplyTo reference ids (vipin-shrivastava)
