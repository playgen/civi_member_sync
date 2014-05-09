CiviMember to WordPress Role Synchronisation Plugin
===========================================================

This plugin is designed to take the hassle out of keeping your users' roles up
 to date with their memberships, thus allowing you to grant permissions to your
 contributors without worring about when their memebership expires.

This can be combined with custom post types or plugins such as
 [User Access Manager][user-access-manager] to create Members Only areas and
 content on your website.

This plugin requries the [WordPress CiviCRM Plugin][civicrm_wordpress] to be
 installed and active in order to work.

How To Use:
-----------

Before you get started, make sure you have created all of your membership types
 and membership status rules for CiviMember as well as the WordPress role(s) you
 would like to synchronize them with.

1. Download and install the plugin. This can be done automatically by using
    'Upload' on the [Add New Plugins][Plugins_Add_New_Screen] page or
    [manually via FTP][Manual_Plugin_Installation].

2. Go to the settings page ("CiviCRM <-> WP Sync") and choose "Add New"

3. Choose the membership type, WP role and when it should be considered active.  
   Typically this will be `New`, `Current` and `Grace`, though if you have
    custom statuses defined this will be different.
   You can also define the WP role the user will get if they have a memberhip  
    that isn't active. If you don't set this they will have the same access as
    a Subscriber.

4. Once you have added rules for each of your membership types you need to make
    sure all existing Civi users have the role "Civi Sync User" set.  
   **WARNING**: The "Civi Sync User" role will replace existing roles such as
                "Editor" or "Admin"! Make sure *all* your rules are set up
                before doing this!

5. Perform a manual synchronisation. This only needs to be done when you modify
    important rules.

It is advisable to not use this plugin on admin accounts so that you still have
 access to the site in the case of a CiviCRM hiccup.
Please test your rules before using them in production.

[user-access-manager]: http://wordpress.org/plugins/user-access-manager/
[civicrm_wordpress]: https://wiki.civicrm.org/confluence/display/CRMDOC/WordPress+Installation+Guide+for+CiviCRM+4.4
[Plugins_Add_New_Screen]: http://codex.wordpress.org/Plugins_Add_New_Screen
[Manual_Plugin_Installation]: http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation
