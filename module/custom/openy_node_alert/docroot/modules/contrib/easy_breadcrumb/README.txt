The Easy Breadcrumb module provides configurable breadcrumbs that improve on
core breadcrumbs by including the current page title as an unlinked crumb which
follows breadcrumb best-practices
(URL "https://www.nngroup.com/articles/breadcrumb-navigation-useful/" ).
Easy Breadcrumb takes advantage of the work you've already done for generating
your path aliases, while it naturally encourages the creation of semantic
and consistent paths. This module is currently available for Drupal 6.x, 7.x,
and 8.x.x.

Easy Breadcrumb uses the current URL (path alias) and the current page's title
to automatically extract the breadcrumb's segments and its respective links.
The module is really a plug and play module because it auto-generates the
breadcrumb by using the current URL and nothing extra is needed.

For example, having an URL like "gallery/videos/once-a-time-in-cartagena",
EasyBreadcrumb will automatically produces the breadcrumb
"Home >> Gallery >> Videos >> Once a time in Cartagena" or
"Home >> Videos >> Once a Time in Cartagena". Again, the breadcrumb presentation
will vary depending on your module's settings.

Requirements
  * None

Recommended modules:
  * None

Configuration:

  To start using it, just go to the admin modules page
  (URL "admin/modules"), locate it under the category "Other" and activate
  it. The system breadcrumb block has now been updated.

  The configuration page of this module is under
  "Admin > Configuration > User Interface > Easy Breadcrumb"
  (URL "admin/config/user-interface/easy-breadcrumb").

  Configurable parameters:

    * Include / Exclude the front page as a segment in the breadcrumb.
    * Include / Exclude invalid path alias as plain-text segments.
    * Include / Exclude the current page's title as a segment in the breadcrumb.
    * Use the page's title when it is available instead of always deducing
      it from the URL.
    * Print the page's title as a link or as a plain-text segment.
    * Use a custom separator between the breadcrumb's segments. (TODO)
    * Choose a transformation mode for the segments' title.(TODO)
    * Make the 'capitalizator' ignore some words (TODO)

Module Page: http://drupal.org/project/easy_breadcrumb
