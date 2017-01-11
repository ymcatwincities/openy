PROJECT
-------------
https://www.drupal.org/project/contact_storage


INSTALLATION
-------------
1. Download and extract the module to your sites/all/modules/contrib folder.
2. Enable the module on the Drupal Modules page (admin/modules) or using :

   $ drush en

If you want to be able to send messages in HTML format, the Swiftmailer module
is required. To install it, follow the same instructions as above, using the
following module :

https://www.drupal.org/project/swiftmailer

The module administration page is available at : /admin/structure/contact
The module settings page is available at : /admin/structure/contact/settings
Adding a new form can be done at : /admin/structure/contact/add
A listing of sent messages is available at : /admin/structure/contact/messages

INSTRUCTIONS TO ENABLE HTML
-------------
In order to be able to send messages in HTML format, once Swiftmailer module
has been installed and enabled, follow these steps :

1. Enable Mail System and select Swiftmailer as your default mail system.
    In "Configuration" -> "Mail System", choose "Swiftmailer" under "Formatter"
    and "Sender".

2. HTML should not be enforced and provided e-mail format should be respected.
    In "Configuration" -> "Swift Mailer", in the "Messages" tab, select "Plain
    Text" under "Message Format" and check the "Respect provided e-mail
    format." option.

3. Enable sending messages in HTML format within Contact Storage.
    In "Structure" -> "Contact forms", in the "Contact settings" tab, check the
    "Send HTML" option.

4. Customize theming.
    The Contact Storage module provides a default template,
    "swiftmailer--contact.html.twig", in /templates directory. This template
    can be changed to conform to your needs.


OVERVIEW
-------------
Contact Storage module will provide storage for Contact messages which are
fully-fledged entities in Drupal 8. This plus core contact module aim to
provide functionality equivalent to the base-features of Webform or Entity
Form. The goal is to firm up this functionality in contrib with view to move
into core in 8.1.x or later.


FEATURES
-------------
Message storage
Edit messages
Admin listing
Views integration


REQUIREMENTS
-------------
Core Contact Module and Swiftmailer module, if sending messages in HTML format
is desired.


CREDITS
-------------
Collaboration between the following developers :

larowlan
jibran
andypost
berdir

Supporting organizations:
PreviousNext (Development time)
MD Systems (Development time)
